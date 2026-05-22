أول خطوة لما تمسك سيستم فيه **POS + Inventory + Accounting** مش إنك تبدأ تدخل فواتير. أول خطوة هي تعمل **Go-Live Setup / Opening Setup** كأن الشركة هتبدأ تشغيل من تاريخ معين.

## 1. حدد تاريخ بداية التشغيل

مثلاً:
**Go-Live Date = 01/06/2026**

أي حاجة قبل التاريخ ده تعتبر Opening Data، وأي حاجة بعده تعتبر Transactions جديدة.

دي نقطة مهمة جدًا لأن الـ ERP migration الصح بيبدأ بتحديد الداتا اللي هتتنقل، وتنضيفها، ومراجعتها قبل التشغيل، مش مجرد إدخال عشوائي. NetSuite توضح أن نجاح نقل الداتا يعتمد على strategy واضحة، data validation، وتقليل البيانات القديمة غير المهمة. ([NetSuite][1])

---

## 2. جهّز Master Data الأول

قبل أي حركة بيع أو شراء لازم تدخل الأساسيات:

### Accounting Master Data

* Chart of Accounts
* Taxes / VAT
* Journals
* Payment Methods
* Banks / Cash accounts
* Customers
* Suppliers

### Inventory Master Data

* Products
* Categories
* Units of Measure
* Warehouses
* Locations / Stores
* Stock valuation method: FIFO / Average Cost / Standard Cost

### POS Master Data

* Branches
* POS devices / cashiers
* Price lists
* Payment methods: Cash, Visa, Wallet
* Receipt settings
* Return/refund policy

الترتيب مهم: لازم الـ master data تتحمل قبل transactions، لأن تحميل الحركات قبل تثبيت العملاء والموردين والأصناف والحسابات بيعمل mapping errors وإعادة شغل. ده مذكور كأفضل ممارسة في تطبيقات ERP مثل NetSuite. ([Concentrus][2])

---

## 3. أدخل Opening Balances

بعد الـ Master Data، تبدأ تدخل الأرصدة الافتتاحية.

### Accounting Opening Balances

تدخل أرصدة:

* Cash
* Bank
* Accounts Receivable
* Accounts Payable
* Inventory value
* Capital / Retained earnings
* Tax payable / receivable
* Loans إن وجدت

الفكرة إن الميزان الافتتاحي لازم يكون متوازن Debit = Credit. في تطبيقات ERP، opening balances تعتبر الأساس اللي يخلي الشركة تكمل تشغيلها وتتابع العملاء، الموردين، المخزون، والالتزامات بشكل صحيح من أول يوم. ([techfino.com][3])

---

## 4. أدخل Opening Stock

هنا تدخل كمية وقيمة المخزون الموجودة فعليًا في كل مخزن.

مثال:

| Product   |  Warehouse | Qty | Unit Cost | Total Value |
| --------- | ---------: | --: | --------: | ----------: |
| Pepsi Can | Main Store | 100 |        10 |       1,000 |
| Chips     | Main Store |  50 |         5 |         250 |

لازم المخزون في الـ Inventory يساوي قيمة حساب المخزون في المحاسبة.
في Odoo مثلًا، عند استخدام automatic inventory valuation، النظام يولد قيود محاسبية مرتبطة بتقييم المخزون بعد إدخال أو تعديل الكميات والقيم. ([Odoo][4])

---

## 5. أدخل Open Transactions فقط

مش لازم تنقل كل التاريخ القديم. الأفضل في المحاكاة أو التشغيل الحقيقي إنك تدخل:

* Open customer invoices
* Open vendor bills
* Unpaid balances
* Pending purchase orders
* Pending sales orders
* Current stock only

أما الفواتير المقفولة القديمة ممكن تخليها archive أو read-only reference. ده متوافق مع أفضل ممارسات ERP migration: التركيز على active master data وopening balances بدل نقل كل التاريخ القديم. ([Tally Solutions][5])

---

## 6. اعمل Reconciliation قبل التشغيل

قبل ما تبدأ simulation، لازم تراجع:

* رصيد البنك في النظام = كشف البنك
* النقدية في النظام = الكاش الفعلي
* المخزون quantity = الجرد الفعلي
* inventory value = حساب المخزون في الميزانية
* العملاء = AR aging
* الموردين = AP aging
* Trial Balance balanced

في مشاريع ERP، من أهم خطوات ما بعد migration هي reconciliation للأرصدة الافتتاحية وتشغيل تقارير مقارنة لفترة للتأكد أن النظام الجديد مطابق للواقع. ([BrokenRubik][6])

---

## 7. بعد كده تبدأ Simulation Cycle

تعمل سيناريو كامل كأنه يوم شغل حقيقي:

1. Purchase Order
2. Goods Receipt
3. Vendor Bill
4. Payment to Supplier
5. Stock appears in inventory
6. POS Sale
7. Cash/Visa collection
8. Inventory decreases
9. COGS recorded
10. Revenue recorded
11. Cashier closing
12. Bank reconciliation
13. Trial Balance / P&L / Balance Sheet

---

## الترتيب المختصر الصحيح

**Go-live date → Master Data → Opening Balances → Opening Stock → Open invoices/bills → Reconciliation → Start POS simulation**

يعني أول حاجة فعليًا تعملها:

**تحدد تاريخ بداية التشغيل، وتجهز Master Data، وبعدها تدخل Opening Balances وOpening Stock قبل أي بيع أو شراء.**

[1]: https://www.netsuite.com/portal/resource/articles/erp/erp-data-migration.shtml?utm_source=chatgpt.com "ERP Data Migration Tips and Best Practices"
[2]: https://concentrus.com/netsuite-implementation-guide/?utm_source=chatgpt.com "Comprehensive NetSuite Implementation Guide for Success"
[3]: https://www.techfino.com/blog/opening-balances-part-1?utm_source=chatgpt.com "Opening Balances - Part 1 - Techfino"
[4]: https://www.odoo.com/documentation/18.0/applications/inventory_and_mrp/inventory/product_management/inventory_valuation/inventory_valuation_config.html?utm_source=chatgpt.com "Automatic inventory valuation — Odoo 18.0 documentation"
[5]: https://tallysolutions.com/erp-software/erp-data-migration-checklist-common-mistakes/?srsltid=AfmBOoqmDEzV2NZIBSb7cbnwfGv4n3Xb3yaeYtjJshQzEFGUgeBvJTcC&utm_source=chatgpt.com "ERP Data Migration Checklist: Avoid Common Mistakes"
[6]: https://www.brokenrubik.com/blog/netsuite-data-migration-guide?utm_source=chatgpt.com "NetSuite Data Migration: Planning, Pitfalls & Best Practices"
