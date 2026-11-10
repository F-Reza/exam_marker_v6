<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Services\AuditService;
use App\Services\SupportTicketNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HelpController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => ['nullable', Rule::in(array_keys(SupportTicket::STATUSES))],
        ]);
        $tickets = $request->user()->supportTickets()->filter($filters)->latest('updated_at')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('help.index', compact('tickets', 'filters') + $this->options());
    }

    public function store(Request $request, AuditService $audit, SupportTicketNotifier $notifier)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:180',
            'category' => ['required', Rule::in(array_keys(SupportTicket::CATEGORIES))],
            'priority' => ['required', Rule::in(array_keys(SupportTicket::PRIORITIES))],
            'message' => 'required|string|min:10|max:10000',
        ]);
        $ticket = DB::transaction(function () use ($request, $audit, $notifier, $data) {
            $ticket = $request->user()->supportTickets()->create($data + ['status' => 'open']);
            $audit->log($ticket, 'support.created', ['category' => $ticket->category, 'priority' => $ticket->priority]);
            $notifier->notify($ticket, $request->user(), 'created');

            return $ticket;
        });

        return redirect()->route('help.show', $ticket)->with('success', 'Support ticket #'.$ticket->id.' created. The support team can now review it.');
    }

    public function show(Request $request, SupportTicket $ticket, SupportTicketNotifier $notifier)
    {
        $this->authorizeTicket($request, $ticket);
        $ticket->load('user', 'messages.user');
        $notifier->markRead($ticket, $request->user());

        return view('help.show', ['ticket' => $ticket] + $this->options());
    }

    public function reply(Request $request, SupportTicket $ticket, AuditService $audit, SupportTicketNotifier $notifier)
    {
        $this->authorizeTicket($request, $ticket);
        $data = $request->validate(['message' => 'required|string|min:1|max:10000']);

        DB::transaction(function () use ($request, $ticket, $audit, $notifier, $data) {
            $locked = SupportTicket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($locked->status, ['resolved', 'closed'], true), 422, 'Reopen this ticket before adding another reply.');
            $locked->preserveLegacyReply();
            $before = $locked->status;
            $locked->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $data['message'],
                'type' => 'message',
                'is_admin' => $request->user()->isPlatformAdmin(),
            ]);
            $changes = ['status' => $request->user()->isPlatformAdmin() ? 'answered' : 'open'];
            if ($request->user()->isPlatformAdmin()) $changes['admin_reply'] = $data['message'];
            $locked->update($changes);
            $locked->touch();
            if ($before !== $locked->status) {
                $locked->messages()->create([
                    'user_id' => $request->user()->id,
                    'message' => 'Status changed from '.(SupportTicket::STATUSES[$before] ?? $before).' to '.SupportTicket::STATUSES[$locked->status].'.',
                    'type' => 'status_change',
                    'is_admin' => $request->user()->isPlatformAdmin(),
                ]);
            }
            $audit->log($locked, $request->user()->isPlatformAdmin() ? 'support.admin_replied' : 'support.user_replied', ['from' => $before, 'to' => $locked->status]);
            $notifier->notify($locked, $request->user(), 'replied');
        });

        return redirect()->route('help.show', $ticket)->with('success', 'Your reply was added to the ticket.');
    }

    public function status(Request $request, SupportTicket $ticket, AuditService $audit, SupportTicketNotifier $notifier)
    {
        $this->authorizeTicket($request, $ticket);
        $data = $request->validate(['status' => ['required', Rule::in(['open', 'closed'])]]);

        DB::transaction(function () use ($request, $ticket, $audit, $notifier, $data) {
            $locked = SupportTicket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === $data['status']) return;
            if ($data['status'] === 'open') {
                abort_unless(in_array($locked->status, ['resolved', 'closed'], true), 422, 'Only a resolved or closed ticket needs to be reopened.');
            }
            $locked->preserveLegacyReply();
            $before = $locked->status;
            $locked->update(['status' => $data['status']]);
            $locked->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $data['status'] === 'closed' ? 'Ticket closed by the requester.' : 'Ticket reopened by the requester.',
                'type' => 'status_change',
                'is_admin' => $request->user()->isPlatformAdmin(),
            ]);
            $audit->log($locked, 'support.status_changed', ['from' => $before, 'to' => $data['status']]);
            $notifier->notify($locked, $request->user(), 'status_changed');
        });

        return redirect()->route('help.show', $ticket)->with('success', $data['status'] === 'closed' ? 'Ticket closed.' : 'Ticket reopened.');
    }

    private function authorizeTicket(Request $request, SupportTicket $ticket): void
    {
        abort_unless($ticket->user_id === $request->user()->id || $request->user()->isPlatformAdmin(), 403);
    }

    private function options(): array
    {
        return ['statuses' => SupportTicket::STATUSES, 'categories' => SupportTicket::CATEGORIES, 'priorities' => SupportTicket::PRIORITIES];
    }
}
