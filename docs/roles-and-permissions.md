# Roles and permissions

Authorization is enforced by policies, Form Requests, and controller gates. Navigation mirrors these decisions but is not the security boundary.

| Capability | Super Admin | HR Admin | Manager | Employee |
|---|:---:|:---:|:---:|:---:|
| View global dashboard metrics | ✓ | ✓ | Limited | Limited |
| Manage roles and Super Admin accounts | ✓ |  |  |  |
| Manage organization master data | ✓ | ✓ |  |  |
| Create/update/deactivate employees | ✓ | ✓* |  |  |
| View own profile and sensitive fields | ✓ | ✓ | ✓ | ✓ |
| View team employee directory | ✓ | ✓ | Direct reports |  |
| Upload/delete private documents | ✓ | ✓ |  |  |
| Download own private documents | ✓ | ✓ | Own | Own |
| Submit/cancel own leave | ✓ | ✓ | ✓ | ✓ |
| Review leave | ✓ | ✓ | Direct reports |  |
| View/correct all attendance | ✓ | ✓ | View team | Own only |
| Configure/generate/publish payroll | ✓ | ✓ |  |  |
| View published payslip | ✓ | ✓ | Own | Own |
| Manage announcements | ✓ | ✓ |  |  |
| View notifications | ✓ | ✓ | ✓ | ✓ |
| Export HR reports | ✓ | ✓ |  |  |
| Manage company settings | ✓ |  |  |  |
| View audit log | ✓ |  |  |  |

`*` HR Admin cannot modify or deactivate a Super Admin account. Managers cannot approve their own leave or records belonging to unrelated employees. Payroll salary values and bank data are never exposed through team-manager access.
