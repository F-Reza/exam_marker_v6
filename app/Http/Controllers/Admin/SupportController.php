<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\AuditService;
use App\Services\SupportTicketNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(['inbox', 'feedback', 'solutions'])],
            'q' => 'nullable|string|max:100',
            'status' => ['nullable', Rule::in(array_keys(SupportTicket::STATUSES))],
            'priority' => ['nullable', Rule::in(array_keys(SupportTicket::PRIORITIES))],
            'category' => ['nullable', Rule::in(array_keys(SupportTicket::CATEGORIES))],
        ]);
        $tab = $filters['tab'] ?? 'inbox';
        $query = SupportTicket::query()->with('user')->withCount('messages');
        if ($tab === 'feedback') $query->where('category', 'feedback');
        if ($tab === 'solutions') $this->solutions($query);
        $tickets = $query->filter($filters)->latest('updated_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $counts = [
            'inbox' => SupportTicket::count(),
            'feedback' => SupportTicket::where('category', 'feedback')->count(),
            'solutions' => $this->solutions(SupportTicket::query())->count(),
        ];

        return view('admin.support.index', compact('tickets', 'filters', 'counts', 'tab') + $this->options());
    }

    public function show(Request $request, SupportTicket $ticket, SupportTicketNotifier $notifier)
    {
        $ticket->load('user', 'messages.user');
        $notifier->markRead($ticket, $request->user());

        return view('admin.support.show', ['ticket' => $ticket] + $this->options());
    }

    public function reply(Request $request, SupportTicket $ticket, AuditService $audit, SupportTicketNotifier $notifier)
    {
        $data = $request->validate([
            'message' => 'nullable|required_without:status|string|min:1|max:10000',
            'status' => ['nullable', 'required_without:message', Rule::in(array_keys(SupportTicket::STATUSES))],
        ]);

        DB::transaction(function () use ($request, $ticket, $audit, $notifier, $data) {
            $locked = SupportTicket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $before = $locked->status;
            $hasMessage = filled($data['message'] ?? null);
            $status = $data['status'] ?? ($hasMessage ? 'answered' : $before);
            if (!$hasMessage && $before === $status) return;
            $locked->preserveLegacyReply();

            if ($hasMessage) {
                $locked->messages()->create([
                    'user_id' => $request->user()->id,
                    'message' => $data['message'],
                    'type' => 'message',
                    'is_admin' => true,
                ]);
                $locked->admin_reply = $data['message'];
            }
            $locked->status = $status;
            $locked->save();
            $locked->touch();

            if ($before !== $status) {
                $locked->messages()->create([
                    'user_id' => $request->user()->id,
                    'message' => 'Status changed from '.(SupportTicket::STATUSES[$before] ?? $before).' to '.SupportTicket::STATUSES[$status].'.',
                    'type' => 'status_change',
                    'is_admin' => true,
                ]);
            }
            $audit->log($locked, $hasMessage ? 'support.admin_replied' : 'support.status_changed', ['from' => $before, 'to' => $status]);
            $notifier->notify($locked, $request->user(), $hasMessage ? 'replied' : 'status_changed');
        });

        return redirect()->route('admin.support.show', $ticket)->with('success', 'Support ticket updated.');
    }

    private function solutions($query)
    {
        return $query->whereIn('status', ['resolved', 'closed'])->whereNotNull('admin_reply')->where('admin_reply', '!=', '');
    }

    private function options(): array
    {
        return ['statuses' => SupportTicket::STATUSES, 'categories' => SupportTicket::CATEGORIES, 'priorities' => SupportTicket::PRIORITIES];
    }
}
