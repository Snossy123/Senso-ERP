# Administration – UX flow diagrams

Mermaid diagrams for **navigation** and **primary admin journeys** under the sidebar category **Administration**. Render in GitHub, GitLab, VS Code (preview), or any Mermaid-compatible viewer.

---

## 1. Sidebar entry map

```mermaid
flowchart LR
  SB[Sidebar Administration]
  U[User Management]
  R[Role Management]
  T[Tenant Management]
  S[Settings]
  A[Activity Log]
  SB --> U & R & T & S & A
```

| Label | Primary route (named) |
| ----- | --------------------- |
| User Management | `admin.users.index` |
| Role Management | `admin.roles.index` |
| Tenant Management | `tenants.index` |
| Settings | `admin.settings` |
| Activity Log | `admin.activity.index` |

---

## 2. Operator journey: land on ERP → Administration task

```mermaid
flowchart TD
  L[Login to ERP] --> D[Dashboard]
  D --> M{Open Administration item from sidebar}
  M -->|Users| U1[User list]
  M -->|Roles| R1[Role list]
  M -->|Tenants| T1[Tenant list]
  M -->|Settings| S1[Grouped settings form]
  M -->|Activity| A1[Activity log list]
```

---

## 3. User Management – list → detail → actions

```mermaid
flowchart TD
  UL[User list filters / search] --> UD[User detail]
  UL --> UC[Create user form]
  UL --> UE[Edit user]
  UD --> UA[View activity snippet]
  UE --> UP[Assign role branch permissions]
  UD --> UT[Toggle active JSON]
  UD --> ULK[Lock / Unlock]
  UD --> URP[Reset password / Force change]
```

---

## 4. Role Management – CRUD and permissions

```mermaid
flowchart TD
  RL[Role list] --> RC[Create role]
  RL --> RE[Edit role]
  RC --> RP[Select permissions by group]
  RE --> RP
  RL --> RD{Delete role}
  RD -->|OK| RLO[Return to list]
  RD -->|In use| ERR[Error message]
```

---

## 5. Tenant Management – lifecycle

```mermaid
flowchart TD
  TL[Tenant list] --> TC[Create tenant trial]
  TC --> TPA[Provision tenant admin user]
  TPA --> TS[Tenant detail]
  TL --> TS
  TS --> TE[Edit tenant plan dates]
  TS --> TT[Toggle active]
  TS --> TSU[Suspend / Activate]
  TS --> TPL[Upgrade plan]
  TS --> TUS[Patch currency language timezone]
  TS --> TLU[Login as tenant user]
  TS --> TSY[Sync usage]
  TL --> TD{Delete tenant}
  TD -->|Has users| BLOCK[Blocked with message]
  TD -->|No users| OK[Deleted redirect]
```

---

## 6. Settings – grouped save

```mermaid
flowchart LR
  SG[Choose group tab] --> SF[Edit fields file upload for logo]
  SF --> SS[POST admin.settings.store with group]
  SS --> SC[Flash success per group]
```

---

## 7. Activity Log – investigate and export

```mermaid
flowchart TD
  AL[Index with filters] --> AD[Activity detail]
  AL -->|export query| CSV[Stream CSV download]
  AD --> AM[Inspect model type id severity]
```

---

## 8. Access pattern (conceptual)

```mermaid
flowchart TD
  REQ[HTTP request] --> AUTH{Authenticated}
  AUTH -->|No| X401[Redirect login]
  AUTH -->|Yes| RES[Resolve resource tenant]
  RES --> POL{Policy permission or isAdmin}
  POL -->|Deny| X403[403 blade / abort]
  POL -->|Allow| OK[Controller + view / JSON]
```

**Note**: Activity Log controller requires **platform admin** (`isAdmin()`). User list and role list allow delegated access via permissions where middleware is applied.

---

## 9. Related documents

- [BRD.md](./BRD.md)
- [FRD.md](./FRD.md)
- [TDD.md](./TDD.md)
