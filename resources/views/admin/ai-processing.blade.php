@extends('layouts.app')

@section('title', 'AI Processing')
@section('page-title', 'AI Processing')

@section('content')

<div class="ai-processing-page">
    <div class="welcome">
        <div>
            <h1>🤖 AI Processing Dashboard</h1>
            <p>Monitor AI paper checking pipeline.</p>
        </div>
    </div>

    <div class="ai-stat-grid">
        @forelse($stats as $key => $value)
            <div class="ai-stat-card">
                <h2>{{ ucfirst(str_replace('_', ' ', $key)) }}</h2>
                <h1>{{ $value }}</h1>
            </div>
        @empty
            <div class="ai-stat-card">
                <h2>No Data</h2>
                <h1>0</h1>
            </div>
        @endforelse
    </div>

    <div class="queue-card">
        <h2>📋 AI Processing Queue</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                        <tr>
                            <td>#{{ $job->id }}</td>
                            <td>{{ $job->student?->name ?? 'N/A' }}</td>
                            <td>
                                <span class="status-badge status-{{ $job->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                                </span>
                            </td>
                            <td title="{{ $job->created_at->format('Y-m-d H:i:s') }}">
                                {{ $job->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No jobs in the queue.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($jobs, 'links') && $jobs->hasPages())
            <div class="pagination-wrapper">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>
</div>

@endsection

@push('styles')
<style>
    /* ============================================
       AI PROCESSING PAGE STYLES
    ============================================ */

    .ai-processing-page .welcome {
        margin-bottom: 25px;
        padding: 20px 24px;
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }

    .ai-processing-page .welcome h1 {
        font-size: 24px;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 8px 0;
    }

    .ai-processing-page .welcome p {
        color: #64748b;
        margin: 0;
    }

    /* Stats Grid */
    .ai-processing-page .ai-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        margin-bottom: 25px;
    }

    @media (max-width: 992px) {
        .ai-processing-page .ai-stat-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .ai-processing-page .ai-stat-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 400px) {
        .ai-processing-page .ai-stat-grid {
            grid-template-columns: 1fr;
        }
    }

    .ai-processing-page .ai-stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 22px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        transition: all 0.3s ease;
    }

    .ai-processing-page .ai-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
    }

    .ai-processing-page .ai-stat-card h2 {
        font-size: 13px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        margin: 0 0 8px 0;
    }

    .ai-processing-page .ai-stat-card h1 {
        font-size: 32px;
        color: #1e293b;
        margin: 0;
        font-weight: 700;
    }

    /* Queue Card */
    .ai-processing-page .queue-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }

    .ai-processing-page .queue-card h2 {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 20px 0;
    }

    /* Table */
    .ai-processing-page .table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .ai-processing-page table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .ai-processing-page table thead th {
        text-align: left;
        padding: 12px 16px;
        color: #64748b;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
        background: #f8fafc;
    }

    .ai-processing-page table tbody td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle;
    }

    .ai-processing-page table tbody tr:hover {
        background: #f8fafc;
    }

    .ai-processing-page table tbody tr:last-child td {
        border-bottom: none;
    }

    .ai-processing-page table .text-center {
        text-align: center;
        color: #94a3b8;
        padding: 30px 0;
    }

    /* Status Badge */
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        min-width: 80px;
        text-align: center;
    }

    /* Status colors based on job status */
    .status-badge.status-pending,
    .status-badge.status-queued {
        background: #fef3c7;
        color: #92400e;
    }

    .status-badge.status-processing {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-badge.status-completed,
    .status-badge.status-finalised {
        background: #dcfce7;
        color: #166534;
    }

    .status-badge.status-failed,
    .status-badge.status-error {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-badge.status-review_required {
        background: #fef3c7;
        color: #92400e;
    }

    .status-badge.status-uploaded {
        background: #e0e7ff;
        color: #3730a3;
    }

    .status-badge.status-ai_checked {
        background: #f3e8ff;
        color: #6b21a8;
    }

    /* Pagination */
    .pagination-wrapper {
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid #e2e8f0;
    }

    .pagination-wrapper .pagination {
        display: flex;
        justify-content: center;
        gap: 6px;
        list-style: none;
        padding: 0;
        margin: 0;
        flex-wrap: wrap;
    }

    .pagination-wrapper .pagination li {
        display: inline-block;
    }

    .pagination-wrapper .pagination li a,
    .pagination-wrapper .pagination li span {
        display: inline-block;
        padding: 6px 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        color: #334155;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.2s ease;
        min-width: 36px;
        text-align: center;
    }

    .pagination-wrapper .pagination li a:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }

    .pagination-wrapper .pagination li.active span {
        background: #6366f1;
        border-color: #6366f1;
        color: white;
    }

    .pagination-wrapper .pagination li.disabled span {
        color: #94a3b8;
        cursor: not-allowed;
        background: #f8fafc;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .ai-processing-page .ai-stat-card h1 {
            font-size: 24px;
        }

        .ai-processing-page .queue-card {
            padding: 16px;
        }

        .ai-processing-page table thead th,
        .ai-processing-page table tbody td {
            padding: 8px 12px;
            font-size: 13px;
        }

        .status-badge {
            min-width: 60px;
            font-size: 11px;
            padding: 3px 10px;
        }

        .ai-processing-page .ai-stat-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
    }

    @media (max-width: 480px) {
        .ai-processing-page .ai-stat-card {
            padding: 16px;
        }

        .ai-processing-page .ai-stat-card h1 {
            font-size: 20px;
        }

        .ai-processing-page table {
            font-size: 12px;
        }

        .ai-processing-page table thead th,
        .ai-processing-page table tbody td {
            padding: 6px 8px;
        }

        .status-badge {
            min-width: 50px;
            font-size: 10px;
            padding: 2px 8px;
        }

        .ai-processing-page .ai-stat-grid {
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
    }

    @media (max-width: 360px) {
        .ai-processing-page .ai-stat-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }
    }
</style>
@endpush