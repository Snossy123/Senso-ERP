# Ecommerce – UX flow diagrams

Mermaid diagrams for primary journeys. Render in GitHub, GitLab, VS Code (preview), or any Mermaid-compatible viewer.

---

## 1. Customer shopping journey (happy path)

```mermaid
flowchart TD
  A[Land on /store home] --> B{Browse categories / promos}
  B --> C[Open product PDP]
  C --> D{Add to cart}
  D --> E[View cart]
  E --> F{Proceed to checkout}
  F --> G[Enter shipping / contact]
  G --> H[Place order]
  H --> I[Order success page]
  I --> J{Optional: create account / login}
```

---

## 2. Guest vs authenticated paths

```mermaid
flowchart LR
  subgraph guest [Guest]
    G1[Browse PDP cart]
    G2[Checkout]
    G3[Success]
    G1 --> G2 --> G3
  end
  subgraph auth [Logged-in customer]
    A1[Browse PDP cart]
    A2[Checkout prefilled optional]
    A3[Success]
    A4[Account orders]
    A1 --> A2 --> A3 --> A4
  end
```

---

## 3. Cart state machine (conceptual)

```mermaid
stateDiagram-v2
  [*] --> Empty
  Empty --> HasItems : Add product
  HasItems --> Empty : Remove all lines
  HasItems --> HasItems : Update qty
  HasItems --> Checkout : Checkout CTA
  Checkout --> HasItems : Back / validation fail
  Checkout --> Placed : Order created
  Placed --> [*]
```

---

## 4. Checkout sequence

```mermaid
sequenceDiagram
  participant U as Shopper
  participant S as Store UI
  participant C as CheckoutController
  participant O as Order layer
  U->>S: Open /store/checkout
  S->>C: GET checkout form
  C-->>S: Form + context
  U->>S: Submit order
  S->>C: POST checkout
  C->>O: Create order
  O-->>C: Order id / result
  C-->>S: Redirect success
  S-->>U: Confirmation
```

---

## 5. Customer account (post-purchase)

```mermaid
flowchart TD
  L[Login /store/login] --> D[Account dashboard]
  D --> P[Profile edit]
  D --> OL[Orders list]
  OL --> OD[Order detail]
```

---

## 6. ERP staff: storefront change (draft → publish)

```mermaid
flowchart TD
  E[Open Storefront Builder or Studio] --> V[Edit sections / layout schema]
  V --> D[Save draft]
  D --> P{Publish}
  P --> PV[New publish version + snapshot]
  PV --> LIVE[Live /store uses published payload]
  D --> R{Rollback?}
  R -->|Yes| RB[Restore prior version]
  RB --> LIVE
```

---

## 7. Studio preview vs live (conceptual)

```mermaid
flowchart LR
  subgraph draft [Draft editing]
    ST[Visual Store Studio]
    API[Layout APIs]
    ST --> API
  end
  subgraph live [Customer-facing]
    SF[Published snapshot]
    R[StorefrontRenderer]
    SF --> R
  end
  ST -.->|iframe preview / same tenant| PV[/store preview/]
  API --> D[(Draft page layout_schema)]
  SF --> L[(Published version)]
```

---

## 8. Related documents

- [BRD.md](./BRD.md)
- [FRD.md](./FRD.md)
- [TDD.md](./TDD.md)
