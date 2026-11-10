# Feature Matrix

| Feature | Free | Mode 1 | Mode 2 | Mode 3 |
|---|---|---|---|---|
| QP + optional MS + WA | Yes | Yes | Yes | Yes |
| Question-wise marks | Yes | Yes | Yes | Yes |
| Total marks / basic feedback | Yes | Yes | Yes | Yes |
| Checks | 1 lifetime | Plan limit | Plan limit | Plan limit |
| Student records | No | Limited | Yes | Yes |
| Bulk WA | No | No | Yes | Yes |
| Bulk student import | No | No | Yes | Yes |
| Percentage / grade | No | No | Yes | Yes |
| Graphs / topic analysis | No | No | Yes | Yes |
| Edit AI marks/feedback | No | No | Yes | Yes |
| Selected-question recheck | No | No | Yes | Yes |
| Result report download | No | No | Yes | Yes |
| Corrected-paper package | No | No | No | Yes |
| Parent details | No | No | No | Yes |
| Email/mobile parent report | No | No | No | Yes |
| Multiple teachers | No | No | No | Yes |
| Teacher-wise allocation | No | No | No | Yes |

## Navigation and account roles

Sidebar links and protected routes use `User::canAccessModule()`. Feature flags and limits remain controlled by the plan stored in the database; changing a plan does not require a code change.

| Area | Free | Mode 1 | Mode 2 | Mode 3 |
|---|---|---|---|---|
| Own papers, question-wise marks and basic feedback | Yes | Yes | Yes | Yes |
| Support Center, profile settings, notifications and billing | Yes | Yes | Yes | Yes |
| Student management | Hidden | Yes | Yes | Yes |
| Bulk upload, analytics and reports | Hidden | Hidden | Yes | Yes |
| Parent messages | Hidden | Hidden | Hidden | Yes |
| Teachers & Team / teacher allocation | Hidden | Hidden | Hidden | Coaching administrator only |

- **Individual teacher:** uses their own plan and papers. A Free account has one lifetime paper check; a paper already uploaded remains available in My Papers.
- **Coaching administrator:** manages the organization's students, teachers and papers, subject to its plan.
- **Assigned teacher:** uses the coaching owner's current plan and sees only their own or assigned papers. They cannot delete papers owned by the coaching account or manage its team/plan purchases.
- **Student:** can upload a personal paper and view their own results. Linked teacher-owned results appear only after finalisation. Student accounts cannot manage students, run bulk checks/analytics, edit/recheck/finalise marks or send parent communications, even if assigned a paid plan.
- **Platform administrator:** accesses platform management and all modules. A teacher's display/job title does not grant administrator privileges.
- **Support Center:** ordinary users open `/help` and create their own tickets; platform administrators open `/admin/support`.
- **Unavailable pages:** HTML GET requests redirect to billing for a plan limitation, or to the dashboard for a role limitation. JSON requests and disallowed changes remain forbidden.
- **Results:** percentage/grade displays and mark/comment/recheck/download controls follow their respective permissions. Finalised results must be reopened before marks or comments can change; reopening revokes the previous parent report link.
