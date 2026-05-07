# NovaKeys legal policies — first draft (2026-05-07)

## Status & disclaimer

**This is an AI-drafted starting copy. It is NOT a substitute for a Saudi-qualified attorney's review.** Before any of this replaces the `ng-pending` "Draft — pending legal review" chips on the live site (publish-readiness blocker B2), an operator-engaged counsel must:

1. Verify each clause against the latest text of the cited KSA statutes.
2. Confirm refund/warranty/return windows match what the merchant actually offers operationally.
3. Add jurisdiction-specific disclosures the merchant has decided to commit to (return-shipping payer, claim turnaround SLAs, exact retention periods).
4. Translate any clause counsel rewrites into Arabic with native legal-register parity.

Drafts are grounded in:

| Authority | Citation |
| --- | --- |
| KSA E-Commerce Law | نظام التجارة الإلكترونية — Royal Decree M/126 |
| KSA Consumer Protection | نظام حماية المستهلك (and its implementing regulations) |
| PDPL | نظام حماية البيانات الشخصية — Royal Decree M/19, in force from Sept 2024 |
| VAT | نظام ضريبة القيمة المضافة — 15% inclusive on B2C in KSA |
| Anti-Cybercrime | نظام مكافحة جرائم المعلوماتية |

## Parties & legal structure

NovaKeys is operated under a **two-entity** structure. The legal copy below names both wherever the difference matters; the rest of the policies reference whichever entity is the contracting party for that purpose.

| Role | Entity | Jurisdiction | Identifier | Address |
| --- | --- | --- | --- | --- |
| **Brand owner / parent** (IP, software, infrastructure) | NEOTECHNOLOGY SOLUTIONS LLC | Wyoming, USA | EIN **36-5148912** · WY Articles of Organization filing **2025-001744917** (filed 14 Aug 2025) | Principal office: 1021 E Lincolnway Suite 8983, Cheyenne, WY 82001 · Registered agent: **FBRA LLC**, 1023 E Lincolnway, Cheyenne, WY 82001 |
| **KSA merchant of record** (the entity on the ZATCA tax invoice; the entity you contract with for the sale itself) | NovaKeys Store — sole proprietorship of FAHAD SAAD FAHAD ALMANSOUR | Kingdom of Saudi Arabia | CR 7053130576 · ZATCA 3145127947 · Chamber 1238532 | (KSA — see /legal page) |

**Plain-English summary:** NeoTechnology Solutions LLC (US, Wyoming) owns the NovaKeys brand and runs the technology platform. The actual sale of the gift-card product to a KSA customer is made by the KSA-registered NovaKeys Store sole proprietorship, because KSA e-commerce / VAT law requires a locally-registered merchant for ZATCA invoicing.

**For the customer** — when you buy on novakeys.store you contract with the **KSA NovaKeys Store** (CR 7053130576). The Wyoming parent is disclosed for transparency about brand ownership and underlying technology operations; it is **not** the merchant on your invoice and is **not** the party you make a return / warranty / refund claim against.

**Confirmed values (sourced from the filing record + IRS taxpayer record):**

```
Entity name (legal)     NeoTechnology Solutions LLC
Entity name (IRS)       NEOTECHNOLOGY SOLUTIONS LLC (taxpayer-record casing)
Member                  FAHAD ALMANSOUR (sole member)
EIN                     36-5148912            (IRS CP 575 G, dated 2025-09-03)
WY Articles filing #    2025-001744917        (Wyoming Sec. of State, 2025-08-14 10:41)
Registered agent        FBRA LLC
Registered office       1023 E Lincolnway, Cheyenne, WY 82001
Principal office        1021 E Lincolnway Suite 8983, Cheyenne, WY 82001
Mailing address         1021 E Lincolnway Suite 8983, Cheyenne, WY 82001
Organizer               Firstbase (1023 E Lincolnway, Cheyenne, WY 82001)
Phone (canonical)       +966 57 013 1122      (the KSA mobile — same number serves
                                                both the KSA merchant and the NTS LLC
                                                public contact; no separate US line)
Email (canonical)       support@novakeys.store (single contact channel for both
                                                entities, per operator decision)
```

These slot into the `NK_CR['parent']` block in `theme-bridge.php` and into any policy body that names the parent. The registered-agent address (1023 E Lincolnway) is the legal-service address; consumer-facing disclosures should generally show the **principal office** (1021 E Lincolnway Suite 8983) as the contact address — counsel can confirm.

## How to port into the codebase

The five policies live in `plugins/novakeys-commerce/includes/theme/theme-bridge.php` inside the `nk_info_pages()` data array (around lines 800–1028 of the live file). Each policy has:

```php
'<key>' => array(
    'kicker'   => '...',
    'h1_en'    => '...',
    'h1_ar'    => '...',
    'lede_en'  => '...',     // ← drop the "pending legal review" wording
    'lede_ar'  => '...',     // ← same
    'draft'    => true,      // ← flip to false when counsel signs off
    'sections' => array(
        array(
            'kicker_en' => '...',
            'h_en'      => '...',
            'h_ar'      => '...',
            'body'      => array(/* paragraphs of EN/AR strings */),
        ),
        // ...
    ),
),
```

Drop every `<span class="ng-pending">مسودة — بانتظار المراجعة القانونية</span>` chip. Replace each draft section's `body` array with the new paragraphs from this doc. Keep sections marked **(KEEP)** verbatim.

---

## 1 — Returns & Refunds (`returns`)

### Updated lede

> **EN:** This policy sets out the conditions under which orders placed on novakeys.store may be returned and refunded. It tracks the statutory baseline under the KSA E-Commerce Law and the Consumer Protection regime, with extensions specific to NovaKeys's digital-goods catalogue.
>
> **AR:** توضح هذه السياسة شروط استرجاع الطلبات على novakeys.store واسترداد قيمتها. وتنطلق من الحد الأدنى الذي يقرّره نظام التجارة الإلكترونية ونظام حماية المستهلك في المملكة العربية السعودية، مع إضافات مخصّصة لكتالوج المنتجات الرقمية في نيوجين ستور.

### Section 01 — Statutory baseline ⓘ (KEEP — already non-draft)

No change.

### Section 02 — Store-specific terms (REPLACE draft chip)

> **EN:**
>
> 1. **14-day extended window for unredeemed digital codes.** Where a digital code (gift-card, software-key, voucher) has not yet been revealed or activated by you, you may request a refund within fourteen (14) days of delivery. Send the request to support@novakeys.store with your order number; we will verify on the issuing platform that the code remains unused before authorising the refund.
> 2. **No refund once the code is revealed, copied, or redeemed.** Per the digital-goods exception in KSA E-Commerce Law and the platform terms of the underlying issuer (Apple, PlayStation, Steam, Microsoft, etc.), a code's value is consumed at the moment of revelation. Once the code has been displayed in your *My Account → Gift Card Keys* page, copied, or used, it is non-refundable.
> 3. **Wrong-region purchases.** Region-locked codes (for example a US Apple Gift Card vs a Saudi Apple Gift Card) are non-refundable once revealed. Please confirm the region selector on the product page before placing the order. If you reveal a wrong-region code, contact us — we may, at our discretion and where the issuing platform allows, swap to the correct region against a small administrative fee, but we are not obliged to.
> 4. **Refund channel and timing.** Approved refunds are returned via the original payment method (Mada, Apple Pay, STC Pay, Tabby, or card). The refund is processed within three (3) business days of approval; your bank or card issuer may take up to ten (10) further business days to credit the amount.
> 5. **Order cancellation before delivery.** Orders may be cancelled at no charge any time before the code is delivered to your account. Once the code lands in your account, the rules above apply.
>
> **AR:**
>
> 1. **مدة 14 يومًا للأكواد الرقمية غير المُستخدَمة.** إذا لم تكشف عن الكود الرقمي (بطاقة هدية، مفتاح برمجي، قسيمة) أو تُفعّله، يحق لك طلب استرداد قيمته خلال أربعة عشر (14) يومًا من تاريخ تسليمه. أرسل الطلب إلى support@novakeys.store مع رقم الطلب، وسنتحقق من المنصة المُصدِرة من أن الكود لا يزال غير مُستخدَم قبل اعتماد الاسترداد.
> 2. **لا استرداد بعد كشف الكود أو نسخه أو استخدامه.** وفقًا لاستثناء المنتجات الرقمية في نظام التجارة الإلكترونية، ولشروط منصات الإصدار (آبل، بلاي ستيشن، ستيم، مايكروسوفت، وغيرها)، تُستهلك قيمة الكود عند لحظة كشفه. بمجرد ظهور الكود في صفحة *حسابي ← بطاقاتي*، أو نسخه، أو استخدامه، يصبح غير قابل للاسترداد.
> 3. **شراء بمنطقة خاطئة.** الأكواد المرتبطة بمنطقة محددة (كبطاقة آبل أمريكية مقابل بطاقة آبل سعودية) غير قابلة للاسترداد بعد كشفها. يُرجى التأكد من اختيار المنطقة الصحيحة على صفحة المنتج قبل الطلب. في حال كشف كود بمنطقة خاطئة تواصل معنا، وقد نتمكّن من استبداله بمنطقة صحيحة بحسب سياسة المنصة المُصدِرة وبرسوم إدارية، دون أن يكون ذلك إلزامًا علينا.
> 4. **قناة وزمن الاسترداد.** يُعاد المبلغ عبر وسيلة الدفع الأصلية (مدى، Apple Pay، STC Pay، تابي، أو البطاقة الائتمانية). تتم معالجة الاسترداد خلال (3) أيام عمل من اعتماده، وقد يحتاج البنك أو مُصدِر البطاقة إلى (10) أيام عمل إضافية لإيداع المبلغ.
> 5. **إلغاء الطلب قبل التسليم.** يمكن إلغاء الطلب دون رسوم في أي وقت قبل تسليم الكود إلى حسابك. بعد وصول الكود إلى حسابك تسري الأحكام أعلاه.

---

## 2 — Warranty (`warranty`)

### Updated lede

> **EN:** This policy describes the warranty NovaKeys Store provides on the digital codes it sells, the statutory protection that applies in addition, and the procedure for raising a warranty claim.
>
> **AR:** توضّح هذه السياسة الضمان الذي يقدّمه نيوجين ستور على الأكواد الرقمية التي يبيعها، والحماية النظامية المطبَّقة إضافةً إليه، وإجراءات تقديم طلب ضمان.

### Section 01 — Statutory minimum ⓘ (KEEP)

No change.

### Section 02 — Manufacturer warranty ⓘ (KEEP)

No change.

### Section 03 — Claim procedure (REPLACE draft chip)

> **EN:**
>
> 1. **Where the code does not work as advertised** — i.e. the issuing platform reports it as invalid, already redeemed, or expired before the printed expiry — open a claim within seven (7) days of the failure by emailing support@novakeys.store with your order number, the code as it appears in *My Account → Gift Card Keys*, and a screenshot of the platform error.
> 2. **Verification.** We verify the code's status against the issuing platform within two (2) business days. If we confirm the failure was on our side or the supplier's, we proceed to remedy.
> 3. **Remedy.** Our default remedy is replacement with an equivalent unused code. Where replacement is not feasible (for example the platform has discontinued the denomination), we issue a full refund to the original payment method.
> 4. **Out-of-scope claims.** A code lost by you, redeemed against the wrong account, or shared with a third party is outside warranty cover. The encryption-at-rest of codes in our system is described in the privacy policy.
> 5. **Contact.** Email support@novakeys.store · Phone +966 57 013 1122 · Operating hours Sun–Thu 09:00–17:00 Asia/Riyadh.
>
> **AR:**
>
> 1. **إذا لم يعمل الكود كما هو موصوف** — أي رفضت المنصة المُصدِرة الكودَ بصفته غير صالح، أو مُستخدَمًا، أو منتهي الصلاحية قبل التاريخ المعلن — افتح طلب ضمان خلال سبعة (7) أيام من تاريخ الفشل بإرسال بريد إلى support@novakeys.store مع رقم الطلب، والكود كما يظهر في *حسابي ← بطاقاتي*، ولقطة شاشة لرسالة الخطأ من المنصة.
> 2. **التحقق.** نتحقق من حالة الكود لدى المنصة المُصدِرة خلال يومَي عمل (2)، فإن ثبت أن المشكلة من جانبنا أو من المورد بدأنا بالمعالجة.
> 3. **المعالجة.** المعالجة الافتراضية هي استبدال الكود بآخر مكافئ غير مُستخدَم. إن تعذّر الاستبدال (مثلًا أوقفت المنصة هذه الفئة) يُعاد المبلغ كاملًا إلى وسيلة الدفع الأصلية.
> 4. **خارج نطاق الضمان.** الكود الذي تفقده أو تستخدمه على حساب خاطئ أو تشاركه مع طرف ثالث خارج نطاق الضمان. يُشار إلى تشفير الأكواد لدينا في سياسة الخصوصية.
> 5. **التواصل.** البريد support@novakeys.store · الجوال 966570131122+ · ساعات العمل الأحد–الخميس 09:00–17:00 بتوقيت آسيا/الرياض.

---

## 3 — Terms & Conditions (`terms`)

### Updated lede

> **EN:** These Terms & Conditions govern the commercial relationship between you (the customer) and **NovaKeys Store, the KSA-registered sole proprietorship** (CR 7053130576) operating on behalf of NeoTechnology Solutions LLC (Wyoming, USA) when you use novakeys.store. They are written to comply with the KSA E-Commerce Law and the Consumer Protection regime. See *Parties & legal structure* at the top of this document.
>
> **AR:** تحكم هذه الشروط والأحكام العلاقة التجارية بينك (العميل) وبين **نيوجين ستور — المؤسسة الفردية المُسجَّلة في المملكة العربية السعودية** (سجل تجاري 7053130576)، التي تعمل بالنيابة عن شركة NeoTechnology Solutions LLC المُسجَّلة في ولاية وايومنغ بالولايات المتحدة، وذلك عند استخدامك novakeys.store. وقد صيغت بما يتوافق مع نظام التجارة الإلكترونية ونظام حماية المستهلك في المملكة العربية السعودية. انظر *الأطراف وهيكل الكيان القانوني* في أعلى الوثيقة.

### Section 01 — Acceptance (REPLACE draft chip)

> **EN:** By creating an account, placing an order, or browsing novakeys.store after the publication date of these Terms, you accept them in full and acknowledge that they form a binding agreement. If you do not accept any clause, do not use the site.
>
> **AR:** بإنشائك حسابًا أو تقديمك طلبًا أو تصفّحك novakeys.store بعد تاريخ نشر هذه الشروط، فإنك توافق عليها بالكامل وتُقرّ بأنها تشكّل اتفاقية ملزمة. وإن لم تقبل أيًّا من بنودها فلا تستخدم الموقع.

### Section 02 — Customer accounts (REPLACE draft chip)

> **EN:**
>
> 1. **Eligibility.** You must be at least eighteen (18) years old, or a younger person acting with the consent of a guardian, to create an account.
> 2. **Account integrity.** You are responsible for keeping your login credentials confidential and for every order placed under your account. Notify us immediately at support@novakeys.store if you suspect unauthorised access.
> 3. **Accuracy.** Information you provide (name, contact, address, payment) must be accurate and current. Inaccurate information may result in order cancellation without refund of any expended payment-processor fees.
> 4. **Termination.** You may close your account at any time by contacting support. We may suspend or close accounts that violate these Terms or the Acceptable Use Policy. Outstanding gift-card codes are honoured for the statutory minimum period after closure.
>
> **AR:**
>
> 1. **الأهلية.** يجب أن تكون قد بلغت ثمانية عشر (18) سنة، أو أن تتصرّف بإذن وليّ أمر، لإنشاء الحساب.
> 2. **سلامة الحساب.** أنت المسؤول عن سرية بيانات الدخول وعن كل طلب يُقدَّم من حسابك. أبلغنا فورًا على support@novakeys.store إذا اشتبهت بوصول غير مُصرَّح به.
> 3. **دقة البيانات.** يجب أن تكون البيانات التي تُدخلها (الاسم، التواصل، العنوان، الدفع) دقيقة ومحدَّثة. قد يترتّب على عدم الدقة إلغاء الطلب دون استرداد رسوم بوابة الدفع المُستحقَّة.
> 4. **إنهاء الحساب.** يمكنك إغلاق حسابك بالتواصل مع الدعم. ولنا أن نُعلّق أو نُغلق الحسابات التي تخالف هذه الشروط أو سياسة الاستخدام. تظل الأكواد المُسلَّمة سارية للحدّ الأدنى النظامي بعد الإغلاق.

### Section 03 — Orders, pricing, payment (REPLACE draft chip)

> **EN:**
>
> 1. **Currency.** All prices are listed in Saudi Riyals (SAR) and **include 15% VAT** as required by KSA tax law. Foreign-currency conversions are made by your card issuer.
> 2. **Order acceptance.** Placing an order is an offer. The contract is concluded when we send the order-confirmation email **and** the gift-card code is delivered to your *My Account → Gift Card Keys* page. We reserve the right to decline an order — for example where stock from the upstream supplier is unavailable, where the order trips fraud-check rules, or where the price was clearly mis-stated.
> 3. **Pricing.** We make reasonable efforts to ensure listed prices are accurate. If a clear pricing error is detected before delivery, we will contact you with the corrected price; you may proceed at the corrected price or cancel for a full refund.
> 4. **Payment methods.** Mada, Apple Pay, STC Pay, Tabby, and major credit/debit cards. All payment processing is performed by the relevant gateway; we do not store full card numbers.
> 5. **Tax invoices.** A tax invoice (`فاتورة ضريبية`) showing the VAT breakdown is issued for every paid order, in line with KSA tax-authority (ZATCA) requirements.
>
> **AR:**
>
> 1. **العملة.** جميع الأسعار بالريال السعودي (SAR) **وتشمل ضريبة القيمة المضافة 15%** بموجب نظام الضرائب السعودي. تُجري بنوك العملاء أي تحويل عُملة لازم.
> 2. **قبول الطلب.** تقديم الطلب يُعدّ إيجابًا. ويُبرَم العقد عند إرسالنا بريد تأكيد الطلب **و** تسليم كود البطاقة إلى صفحتك في *حسابي ← بطاقاتي*. ولنا الحق في رفض الطلب — مثلًا عند نفاد المخزون لدى المورد، أو إثارته لقواعد التحقق من الاحتيال، أو وجود خطأ سعري واضح.
> 3. **الأسعار.** نبذل جهودًا معقولة لضمان دقة الأسعار. وإذا اكتُشف خطأ سعري واضح قبل التسليم سنتواصل معك بالسعر المُصحَّح، ولك الخيار بين المتابعة بالسعر الجديد أو إلغاء الطلب واسترداد قيمته كاملًا.
> 4. **وسائل الدفع.** مدى، Apple Pay، STC Pay، تابي، وبطاقات الائتمان والخصم الرئيسية. تُعالَج المدفوعات عبر بوابة الدفع المعنية ولا نُخزّن أرقام البطاقات الكاملة.
> 5. **الفاتورة الضريبية.** تصدر فاتورة ضريبية متوافقة مع متطلبات الفوترة الإلكترونية للمرحلة الثانية لدى هيئة الزكاة والضريبة والجمارك (ZATCA) عن كل طلب مدفوع.

### Section 04 — Intellectual property (REPLACE draft chip)

> **EN:** The "NovaKeys" / "نيوجين ستور" name, the NovaKeys logo, the brand-token / design system used on novakeys.store, and the underlying software platform are the property of **NeoTechnology Solutions LLC** (Wyoming, USA — Articles of Organization filing 2025-001744917, EIN 36-5148912), and are licensed to NovaKeys Store (CR 7053130576) for use in connection with the KSA storefront. Brand marks of third parties displayed on our gift-card products (Apple, PlayStation, Steam, Microsoft, etc.) are the property of their respective owners and appear on this site solely to identify the redemption platform — no endorsement is implied. You may not copy, modify, or redistribute site content for commercial use without prior written permission from NeoTechnology Solutions LLC.
>
> **AR:** اسم «NovaKeys» / «نيوجين ستور»، وشعار نيوجين، ومنظومة الهوية والتصميم على novakeys.store، والمنصّة البرمجية الأساسية، جميعها ملك لشركة **NeoTechnology Solutions LLC** (وايومنغ، الولايات المتحدة — رقم قيد التأسيس 2025-001744917، الرقم الضريبي الفيدرالي EIN 36-5148912)، ومُرخَّصة لنيوجين ستور (سجل تجاري 7053130576) لاستخدامها في تشغيل المتجر السعودي. وعلامات الأطراف الأخرى الظاهرة على منتجات بطاقات الهدايا (آبل، بلاي ستيشن، ستيم، مايكروسوفت وغيرها) ملك لأصحابها، وتُعرَض على الموقع لتعريف منصّة الاستخدام فقط ولا تعني أي رعاية أو تأييد. لا يجوز نسخ محتوى الموقع أو تعديله أو إعادة توزيعه لأغراض تجارية دون إذن كتابي مسبق من NeoTechnology Solutions LLC.

### Section 05 — Limitation of liability (REPLACE draft chip)

> **EN:** Subject to mandatory consumer-protection rules that cannot be excluded under KSA law, our maximum aggregate liability for any single order is limited to the price you actually paid for that order, including VAT. We are not liable for indirect or consequential losses (loss of profit, lost data, lost opportunity) arising from use of the site or any product purchased through it. Nothing in this clause excludes liability for fraud, gross negligence, or anything else that cannot be excluded under KSA law.
>
> **AR:** مع مراعاة قواعد حماية المستهلك الإلزامية التي لا يجوز استبعادها بموجب الأنظمة السعودية، تكون مسؤوليتنا التراكمية القصوى عن أي طلب محصورةً في السعر الفعلي الذي دفعته عن ذلك الطلب شاملًا الضريبة. ولا نُسأل عن الأضرار غير المباشرة أو التبعية (فقد الأرباح، ضياع البيانات، ضياع الفرصة) الناشئة عن استخدام الموقع أو أيٍّ من منتجاته. ولا يستثني هذا البند المسؤوليةَ عن الغش أو الإهمال الجسيم أو ما لا يجوز استبعاده نظامًا.

### Section 06 — Governing law (REPLACE draft chip; existing partial sentence retained)

> **EN:** These Terms are governed by the laws of the Kingdom of Saudi Arabia. Disputes that cannot be resolved through our customer-support channel are referred to the competent commercial court of the Kingdom of Saudi Arabia, and to the e-commerce dispute mechanism operated by the Ministry of Commerce where applicable.
>
> **AR:** تخضع هذه الشروط لأنظمة المملكة العربية السعودية. وتُحال النزاعات التي يتعذّر حلّها عبر قناة دعم العملاء إلى المحكمة التجارية المختصة بالمملكة العربية السعودية، وإلى آلية تسوية نزاعات التجارة الإلكترونية لدى وزارة التجارة عند الاقتضاء.

### Section 07 — Changes to these Terms (REPLACE draft chip)

> **EN:** We may update these Terms from time to time to reflect changes in the law, our offering, or our processes. The effective date appears at the top of the document. Material changes are announced via an in-account notice and an email to your registered address at least seven (7) days before they take effect. Continued use of the site after the effective date constitutes acceptance of the updated Terms.
>
> **AR:** قد نُحدّث هذه الشروط من حينٍ لآخر لتعكس تغيّرات الأنظمة أو خدماتنا أو إجراءاتنا. ويظهر تاريخ السريان في رأس الوثيقة. ويُعلَن عن التغييرات الجوهرية عبر إشعار داخل الحساب وبريد إلى عنوانك المسجَّل قبل سريانها بسبعة (7) أيام على الأقل. ويُعدّ استمرارك في استخدام الموقع بعد تاريخ السريان قبولًا للشروط المُحدَّثة.

---

## 4 — Privacy Policy (`privacy`)

### Updated lede

> **EN:** This policy explains what personal data NovaKeys Store (the **data controller** — KSA sole proprietorship, CR 7053130576) collects, why, on what legal basis, with whom we share it (including with our parent NeoTechnology Solutions LLC of Wyoming, USA, acting as a **data processor** for engineering and infrastructure), and the rights you have under the KSA Personal Data Protection Law (PDPL, Royal Decree M/19, in full effect from September 2024).
>
> **AR:** تشرح هذه السياسة البيانات الشخصية التي يجمعها نيوجين ستور (**جهة التحكّم في البيانات** — مؤسسة فردية سعودية، سجل تجاري 7053130576)، وأغراض الجمع، وأسسه النظامية، والجهات التي نشاركها معها (بما في ذلك الشركة الأم NeoTechnology Solutions LLC في ولاية وايومنغ بالولايات المتحدة، بصفتها **جهة معالجة** للبيانات لأغراض الهندسة والبنية التحتية)، وحقوقك بموجب نظام حماية البيانات الشخصية السعودي (PDPL، المرسوم الملكي م/19، النافذ بالكامل من سبتمبر 2024).

### Section 01 — Controller ⓘ (KEEP)

No change.

### Section 02 — Data we collect (REPLACE draft chip)

> **EN:**
>
> 1. **Identity** — name, billing/shipping address, phone, date of birth (only when legally required for purchase verification).
> 2. **Contact** — email address, phone number, optional WhatsApp handle.
> 3. **Order data** — products purchased, prices, payment method (we never store full card numbers; the gateway tokenises), redemption-region selection.
> 4. **Encrypted gift-card codes** — codes you have purchased, stored at-rest with AES-256-GCM encryption; only readable by you when logged in.
> 5. **Device & usage** — IP address, browser user-agent, device type, referring page, pages viewed (cookies, see Section 08).
> 6. **Customer-support correspondence** — messages you send to support@novakeys.store and our replies.
>
> **AR:**
>
> 1. **بيانات الهوية** — الاسم، عنوان الفوترة/الشحن، رقم الهاتف، تاريخ الميلاد (عند طلب نظامي للتحقق من الشراء فقط).
> 2. **بيانات التواصل** — البريد الإلكتروني، رقم الهاتف، حساب واتساب اختياريًّا.
> 3. **بيانات الطلب** — المنتجات المشتراة، الأسعار، وسيلة الدفع (لا نُخزّن أرقام البطاقات الكاملة؛ تُرمَّز عبر بوابة الدفع)، اختيار منطقة الاستخدام.
> 4. **أكواد البطاقات المُشفَّرة** — الأكواد التي اشتريتها، مُخزَّنة باستخدام تشفير AES-256-GCM؛ ولا يقرأها سواك بعد دخولك إلى حسابك.
> 5. **بيانات الجهاز والاستخدام** — عنوان IP، وكيل المتصفح، نوع الجهاز، الصفحة المُحيلة، الصفحات التي زرتها (ملفات الارتباط، انظر القسم 08).
> 6. **مراسلات الدعم** — الرسائل التي ترسلها إلى support@novakeys.store وردودنا عليها.

### Section 03 — Purposes (REPLACE draft chip)

> **EN:** We process your personal data to:
>
> 1. fulfil your order — supply the gift-card code to your account and issue the tax invoice;
> 2. provide customer support — answer questions, resolve warranty claims, troubleshoot redemption errors;
> 3. comply with our regulatory obligations — Commercial Registration record-keeping, ZATCA tax invoicing, Anti-Money-Laundering checks where applicable;
> 4. detect and prevent fraud — flag suspicious purchase patterns, throttle abusive endpoint calls, block known fraud actors;
> 5. improve the storefront — anonymous analytics on what products are viewed, what searches return no results, where customers drop out of checkout;
> 6. communicate marketing — only when you have explicitly opted in, and only until you withdraw consent.
>
> **AR:** نُعالج بياناتك الشخصية للأغراض التالية:
>
> 1. تنفيذ طلبك — تسليم كود البطاقة إلى حسابك وإصدار الفاتورة الضريبية؛
> 2. تقديم الدعم — الرد على الاستفسارات ومعالجة طلبات الضمان وحل مشاكل الاستخدام؛
> 3. الالتزام بالأنظمة — حفظ سجلات السجل التجاري، الفوترة الإلكترونية لدى ZATCA، إجراءات مكافحة غسل الأموال عند الاقتضاء؛
> 4. كشف الاحتيال ومنعه — رصد أنماط الشراء المشبوهة، تقييد الاستدعاءات المُسيئة لنقاط النهاية، حجب الأطراف المعروفة بالاحتيال؛
> 5. تحسين المتجر — تحليلات مجهولة الهوية لما يُشاهد من منتجات وما تُرجِعه عمليات البحث الفارغة وأماكن خروج العملاء من الدفع؛
> 6. التواصل التسويقي — فقط عند موافقتك الصريحة وحتى تسحبها.

### Section 04 — Legal bases under PDPL (REPLACE draft chip)

> **EN:** We rely on one or more of the following lawful grounds, per Article 6 of the PDPL:
>
> | Purpose group | Lawful ground |
> | --- | --- |
> | Order fulfilment, account management | Performance of a contract |
> | Tax invoicing, CR record-keeping, AML | Compliance with a legal obligation |
> | Fraud detection, abuse rate-limiting | Legitimate interest |
> | Marketing communications | Explicit consent |
>
> **AR:** نستند إلى واحد أو أكثر من الأسس النظامية التالية وفق المادة 6 من PDPL:
>
> | غرض المعالجة | الأساس النظامي |
> | --- | --- |
> | تنفيذ الطلب وإدارة الحساب | تنفيذ عقد |
> | الفوترة الضريبية وسجلات السجل التجاري ومكافحة غسل الأموال | الالتزام بنظام |
> | كشف الاحتيال وتقييد الاستخدام المُسيء | المصلحة المشروعة |
> | الرسائل التسويقية | الموافقة الصريحة |

### Section 05 — Sharing (REPLACE draft chip)

> **EN:** We share your personal data only with the parties strictly necessary to operate the service:
>
> 1. **NeoTechnology Solutions LLC (US parent — data processor).** Engineers, hosts, and maintains the novakeys.store platform on behalf of the KSA controller. NTS LLC accesses personal data only as necessary for platform operation, governed by an internal data-processing agreement that follows PDPL processor obligations.
> 2. **Payment gateways** — Mada / SAMA-licensed acquirers, Apple Pay, STC Pay, Tabby, and your card issuer. They receive only the data each needs to authorise the transaction.
> 3. **Hosting and infrastructure** — our website hosting provider and CDN. Data is processed inside their infrastructure to deliver the page you requested.
> 4. **Customer-support tooling** — the email service that handles support@novakeys.store correspondence.
> 5. **Tax authority** — ZATCA receives the tax invoice for every paid order in line with KSA e-invoicing requirements.
> 6. **Regulators and courts** — when required by law (Ministry of Commerce, the Saudi Data & AI Authority, competent courts).
>
> We **do not sell** personal data and we **do not share** it with marketing partners outside your consented marketing communications.
>
> **AR:** لا نُشارك بياناتك الشخصية إلا مع الجهات اللازمة لتشغيل الخدمة:
>
> 1. **NeoTechnology Solutions LLC (الشركة الأم في الولايات المتحدة — جهة معالجة).** تتولّى الهندسة والاستضافة والصيانة لمنصّة novakeys.store بالنيابة عن جهة التحكّم السعودية. لا يصل NTS LLC إلى البيانات الشخصية إلا بالقدر اللازم لتشغيل المنصّة، بموجب اتفاقية معالجة بيانات داخلية تتوافق مع التزامات جهة المعالجة في PDPL.
> 2. **بوابات الدفع** — مدى ومُستحوذي SAMA المرخَّصين، وApple Pay، وSTC Pay، وتابي، ومُصدِر بطاقتك. تتلقى كلّ جهة فقط ما يلزمها لاعتماد المعاملة.
> 3. **الاستضافة والبنية التحتية** — مزوّد الاستضافة وشبكة توصيل المحتوى. تُعالَج البيانات داخل بنيتهم لتسليم الصفحة المطلوبة.
> 4. **أدوات دعم العملاء** — خدمة البريد التي تُدير مراسلات support@novakeys.store.
> 5. **الجهة الضريبية** — تستلم ZATCA الفاتورة الضريبية لكل طلب مدفوع وفق متطلبات الفوترة الإلكترونية في المملكة.
> 6. **الجهات التنظيمية والقضائية** — عند الاشتراط النظامي (وزارة التجارة، الهيئة السعودية للبيانات والذكاء الاصطناعي، المحاكم المختصّة).
>
> **لا نبيع** البيانات الشخصية، **ولا نُشاركها** مع شركاء تسويق خارج نطاق رسائل التسويق التي وافقت عليها.

### Section 06 — Retention (REPLACE draft chip)

> **EN:** We retain personal data only for as long as we need it for the purpose it was collected for, or as required by law:
>
> | Category | Retention period |
> | --- | --- |
> | Order records, tax invoices | Ten (10) years from the order date — minimum required by KSA tax/commercial-records law |
> | Encrypted gift-card codes | Ten (10) years from the order date, alongside the order record |
> | Customer-support correspondence | Five (5) years from the last reply |
> | Marketing-consent record | Until consent is withdrawn, plus two (2) years for proof-of-consent |
> | Server access logs | Ninety (90) days |
>
> **AR:** نحتفظ بالبيانات الشخصية للمدة اللازمة للغرض الذي جُمعت لأجله، أو حسب ما تتطلّبه الأنظمة:
>
> | الفئة | مدة الاحتفاظ |
> | --- | --- |
> | سجلات الطلب والفواتير الضريبية | عشر (10) سنوات من تاريخ الطلب — الحدّ الأدنى الذي تشترطه الأنظمة الضريبية والتجارية |
> | الأكواد المُشفَّرة | عشر (10) سنوات من تاريخ الطلب مع سجل الطلب |
> | مراسلات الدعم | خمس (5) سنوات من آخر ردّ |
> | سجل الموافقة على التسويق | حتى سحب الموافقة، إضافةً إلى سنتين (2) لإثبات الموافقة |
> | سجلات وصول الخادم | تسعون (90) يومًا |

### Section 07 — Your rights under PDPL (REPLACE draft chip)

> **EN:** Under the PDPL you have the right to:
>
> 1. be informed of what we collect and why (this policy);
> 2. access a copy of the personal data we hold about you;
> 3. correct inaccurate personal data;
> 4. delete your personal data subject to our retention obligations;
> 5. restrict or object to processing for marketing or legitimate-interest grounds;
> 6. withdraw consent at any time, where consent is the legal basis;
> 7. data portability — receive your data in a machine-readable format.
>
> Exercise any right by emailing the data-controller contact above. We respond within thirty (30) days. Where we cannot fulfil a request — for example because of an overriding legal-retention obligation — we explain why.
>
> If you believe we have not handled your request properly, you may complain to the Saudi Data & AI Authority (SDAIA) — the PDPL supervisory authority.
>
> **AR:** تكفل لك PDPL الحقوق التالية:
>
> 1. أن تُحاط علمًا بما نجمع وبأغراض الجمع (هذه السياسة)؛
> 2. الاطلاع على نسخة من بياناتك الشخصية لدينا؛
> 3. تصحيح البيانات غير الدقيقة؛
> 4. طلب حذف بياناتك مع مراعاة التزاماتنا في الاحتفاظ؛
> 5. تقييد المعالجة أو الاعتراض عليها لأغراض التسويق أو المصلحة المشروعة؛
> 6. سحب الموافقة في أي وقت متى كانت الأساس النظامي؛
> 7. نقل البيانات — استلام بياناتك بصيغة قابلة للمعالجة الآلية.
>
> تُمارَس الحقوق بالتواصل مع جهة المراقبة عبر بيانات التواصل أعلاه. ونردّ خلال (30) يومًا. وإذا تعذّر الاستجابة — مثلًا بسبب التزام نظامي بالاحتفاظ — نُبيّن السبب.
>
> وإن رأيت أننا لم نُحسن التعامل مع طلبك، يمكنك التقدّم بشكوى إلى الهيئة السعودية للبيانات والذكاء الاصطناعي (SDAIA) — الجهة الإشرافية على PDPL.

### Section 08 — Cookies & tracking (REPLACE draft chip)

> **EN:** We use the following cookie categories:
>
> | Category | Examples | Consent? |
> | --- | --- | --- |
> | Strictly necessary | session, cart, security tokens | No — required for the site to work |
> | Functional | recently-viewed list (`ng_recent`), referral attribution (`nk_ref`) | No (legitimate interest) |
> | Analytics | aggregated, anonymised page-view counters | Yes |
> | Marketing | retargeting pixels, conversion tags | Yes |
>
> A consent banner is displayed on first visit to capture analytics and marketing consent. You can change your preferences at any time from the *Cookies* link in the footer.
>
> **AR:** نستخدم الفئات التالية من ملفات الارتباط:
>
> | الفئة | أمثلة | موافقة مطلوبة؟ |
> | --- | --- | --- |
> | ضرورية | الجلسة، السلة، رموز الأمان | لا — لازمة لعمل الموقع |
> | وظيفية | قائمة المشاهَد مؤخرًا (`ng_recent`)، إحالة الدعوة (`nk_ref`) | لا (مصلحة مشروعة) |
> | تحليلية | عدّادات صفحات مجهولة الهوية | نعم |
> | تسويقية | بكسلات إعادة الاستهداف، علامات التحويل | نعم |
>
> يُعرض شريط موافقة عند أول زيارة لاتخاذ موافقتك على فئتي التحليل والتسويق. ويمكنك تعديل تفضيلاتك في أي وقت من رابط *ملفات الارتباط* في تذييل الموقع.

### Section 09 — Cross-border transfers (REPLACE draft chip)

> **EN:** Personal data flows from the KSA controller to **NeoTechnology Solutions LLC in Wyoming, USA** for platform engineering, hosting, and security operations. Some additional service providers (payment-gateway tokenisation, email service, CDN edge nodes) also host data in jurisdictions outside the Kingdom of Saudi Arabia. Under PDPL Article 29, cross-border transfers are restricted; we rely on:
>
> 1. transfers to jurisdictions recognised by SDAIA as offering adequate protection, where applicable;
> 2. binding contractual safeguards with the recipient (data-processing agreement) where adequacy is not established — this is the basis for the KSA → NTS LLC (Wyoming) flow until SDAIA publishes a US adequacy decision;
> 3. your explicit consent where neither (1) nor (2) is available.
>
> **AR:** تنتقل البيانات الشخصية من جهة التحكّم السعودية إلى **NeoTechnology Solutions LLC في ولاية وايومنغ بالولايات المتحدة** لأغراض هندسة المنصّة واستضافتها وعمليات الأمن. كذلك يستضيف بعض موردي الخدمة الإضافيين (ترميز بوابات الدفع، خدمة البريد، عُقد شبكة توصيل المحتوى) البيانات خارج المملكة العربية السعودية. وبموجب المادة 29 من PDPL يُقيَّد النقل خارج الحدود؛ ونعتمد على:
>
> 1. النقل إلى دول تعترف لها SDAIA بحماية كافية حيثما ينطبق؛
> 2. ضمانات تعاقدية ملزِمة مع المتلقّي (اتفاقية معالجة بيانات) حال عدم اعتماد الكفاية — وهذا هو الأساس لنقل البيانات من المملكة إلى NTS LLC (وايومنغ) ريثما تصدر SDAIA قرار كفاية بشأن الولايات المتحدة؛
> 3. موافقتك الصريحة عند عدم توفّر الخيارَين (1) و(2).

---

## 5 — Acceptable Use Policy (`usage`)

### Updated lede

> **EN:** This policy sets the rules for using novakeys.store, your customer account, and the digital codes you purchase. It is binding alongside the Terms & Conditions and is enforceable under the KSA Anti-Cybercrime Law where conduct crosses the criminal threshold.
>
> **AR:** تُحدّد هذه السياسة قواعد استخدام novakeys.store وحسابك العميل والأكواد الرقمية التي تشتريها. وهي مُلزِمة إلى جانب الشروط والأحكام، وتُنفَّذ بموجب نظام مكافحة جرائم المعلوماتية السعودي عند تجاوز السلوك حدّ التجريم.

### Section 01 — Acceptance (REPLACE draft chip)

> **EN:** By accessing or using novakeys.store you agree to this Acceptable Use Policy. If you do not agree, do not use the site.
>
> **AR:** بدخولك إلى novakeys.store أو استخدامه فإنك توافق على سياسة الاستخدام هذه. وإن لم توافق فلا تستخدم الموقع.

### Section 02 — Prohibited uses (REPLACE draft chip)

> **EN:** You may not, and you may not allow anyone acting on your behalf to:
>
> 1. **Automate, scrape, or harvest** content, prices, or stock data — including via crawlers, headless browsers, or AI agents — beyond what `robots.txt` explicitly allows;
> 2. **Place fraudulent or fictitious orders**, or use payment instruments you are not authorised to use;
> 3. **Impersonate** another person, business, or entity, or misrepresent your affiliation with one;
> 4. **Circumvent** access controls, rate limits, region locks, or fraud-detection systems;
> 5. **Probe, scan, or test** the security of the site without prior written permission, or disclose vulnerabilities outside our coordinated-disclosure channel (security@novakeys.store);
> 6. **Upload or transmit** malware, viruses, or any other malicious code;
> 7. **Resell** purchased gift-card codes outside the redemption channel intended by the issuer (most issuer terms prohibit secondary resale; you remain bound by their rules);
> 8. **Infringe** intellectual-property rights, including those of NeoTechnology Solutions LLC (which owns the NovaKeys brand and platform) and the gift-card issuers whose marks appear on our products;
> 9. **Use the site to violate any KSA law**, including the Anti-Cybercrime Law (نظام مكافحة جرائم المعلوماتية), the Anti-Money-Laundering Law, or sanctions law.
>
> **AR:** يُحظَر عليك ويُحظَر على من يتصرّف نيابةً عنك:
>
> 1. **الأتمتة والكشط والجمع** للمحتوى أو الأسعار أو بيانات المخزون — بما في ذلك عبر زواحف الشبكة أو المتصفّحات بلا واجهة أو وكلاء الذكاء الاصطناعي — بما يتجاوز ما يسمح به `robots.txt` صراحةً؛
> 2. **تقديم طلبات احتيالية أو وهمية**، أو استخدام أدوات دفع لا تملك صلاحيتها؛
> 3. **انتحال** شخصية شخص أو منشأة أو جهة، أو الإيحاء بانتمائك إليها زورًا؛
> 4. **التحايل** على ضوابط الوصول أو حدود الاستخدام أو قيود المناطق أو أنظمة كشف الاحتيال؛
> 5. **اختبار أمن الموقع** أو فحصه دون إذن كتابي مسبق، أو الإفصاح عن ثغرات خارج قناة الإفصاح المنسَّق (security@novakeys.store)؛
> 6. **رفع أو نقل** برمجيات خبيثة أو فيروسات أو أيٍّ من الأكواد الضارّة؛
> 7. **إعادة بيع** أكواد البطاقات خارج قناة الاستخدام التي يقصدها المُصدِر (معظم شروط المُصدِرين تمنع إعادة البيع، وتبقى ملتزمًا بقواعدهم)؛
> 8. **انتهاك حقوق الملكية الفكرية**، بما فيها حقوق شركة NeoTechnology Solutions LLC (مالكة علامة NovaKeys والمنصّة) وحقوق مُصدِري بطاقات الهدايا التي تظهر علاماتهم على منتجاتنا؛
> 9. **استخدام الموقع لمخالفة أي نظام سعودي**، ومنه نظام مكافحة جرائم المعلوماتية ونظام مكافحة غسل الأموال وأنظمة العقوبات.

### Section 03 — Account conduct (REPLACE draft chip)

> **EN:** You are responsible for keeping your login credentials confidential, for the accuracy of the information you supply (delivery address, contact details, payment data), and for every activity that occurs under your account. Notify us at support@novakeys.store the moment you suspect your account has been accessed without your authorisation.
>
> **AR:** أنت المسؤول عن سريّة بيانات الدخول، وعن دقة ما تُقدّمه من معلومات (عنوان التسليم، بيانات التواصل، معلومات الدفع)، وعن كل نشاط يحدث على حسابك. أبلغنا فورًا على support@novakeys.store عند اشتباهك بدخول غير مُصرَّح به.

### Section 04 — User content (REPLACE draft chip)

> **EN:** Where the site allows you to submit content (product reviews, support questions, public comments), you grant us a non-exclusive, worldwide, royalty-free licence to display, store, and moderate that content for the operation of the site. You undertake that the content is lawful, accurate, your own (or licensed for your use), and not defamatory, threatening, or in breach of third-party rights. We may moderate, edit, or remove submissions at our discretion.
>
> **AR:** عندما يتيح الموقع لك تقديم محتوى (تقييمات المنتج، أسئلة الدعم، التعليقات العامة)، فإنك تمنحنا ترخيصًا غير حصري وعالمي ومجاني لعرض ذلك المحتوى وتخزينه وإدارته لأغراض تشغيل الموقع. وتُقرّ بأن المحتوى مشروع ودقيق وعائد إليك (أو مرخَّص لاستخدامك)، وأنه لا ينطوي على تشهير أو تهديد أو إخلال بحقوق الغير. ولنا الحق في تعديل المحتوى أو إزالته متى رأينا ذلك.

### Section 05 — Enforcement (REPLACE draft chip)

> **EN:** Where we reasonably believe you have breached this policy or any applicable law, we may, at our discretion and proportionate to the breach: (i) issue a warning; (ii) suspend access to your account; (iii) cancel pending orders; (iv) close your account; (v) refuse future business; (vi) report the conduct to the competent authorities; and (vii) seek to recover any losses we have suffered. Severe breaches — for example fraud, malware distribution, or coordinated abuse — may result in immediate closure without prior notice.
>
> **AR:** متى توفّر لدينا اعتقاد معقول بأنك أخلَلت بهذه السياسة أو بأي نظام مُطبَّق، يحق لنا، وفق تقديرنا وبما يتناسب مع المخالفة، أن: (1) نُوجّه إنذارًا؛ (2) نُعلّق الوصول إلى حسابك؛ (3) نُلغي الطلبات قيد التنفيذ؛ (4) نُغلق حسابك؛ (5) نمتنع عن مستقبل التعامل؛ (6) نُبلّغ الجهات المختصّة؛ (7) نسعى إلى استرداد أي خسائر لحقت بنا. وقد تؤدّي المخالفات الجسيمة — مثل الاحتيال، أو نشر البرمجيات الخبيثة، أو الإساءة المُنسَّقة — إلى الإغلاق الفوري دون إنذار.

### Section 06 — Reporting abuse (REPLACE draft chip)

> **EN:** Report content or activity you believe violates this policy by emailing **abuse@novakeys.store**. Coordinated security-vulnerability disclosures: **security@novakeys.store**. Both addresses are monitored during business hours (Sun–Thu 09:00–17:00 Asia/Riyadh).
>
> **AR:** أبلغ عن المحتوى أو النشاط الذي تعتقد أنه يخالف هذه السياسة عبر **abuse@novakeys.store**. وللإفصاح المنسَّق عن الثغرات الأمنية: **security@novakeys.store**. يُتابَع البريدان خلال ساعات العمل (الأحد–الخميس 09:00–17:00 بتوقيت آسيا/الرياض).

---

## After counsel sign-off — port checklist

In `plugins/novakeys-commerce/includes/theme/theme-bridge.php`, function `nk_info_pages()`:

- [ ] **Returns** — replace `lede_en` / `lede_ar` (drop "pending legal review"); replace section 02 `body`; flip `'draft' => false`.
- [ ] **Warranty** — replace `lede_en` / `lede_ar`; replace section 03 `body`; flip `'draft' => false`.
- [ ] **Terms** — replace `lede_en` / `lede_ar`; replace bodies of sections 01–07 (all of them); flip `'draft' => false`.
- [ ] **Privacy** — replace `lede_en` / `lede_ar`; replace bodies of sections 02–09 (section 01 stays); flip `'draft' => false`.
- [ ] **Acceptable Use** — replace `lede_en` / `lede_ar`; replace bodies of sections 01–06; flip `'draft' => false`.

In the same file, the `NK_CR` const (around line 22):

- [ ] Add a `parent` entry alongside `legal_name_*`/`brand_*`/`cr` describing NeoTechnology Solutions LLC — name, jurisdiction (Wyoming, USA), filing number, EIN. The `/legal` page pattern (`themes/novakeys/patterns/legal-disclosure.php`) will need a small block to render the parent entity below the regulatory rows.
- [ ] Add a `parent` row to the `NK_CR['regulatory']` array (or a new top-level `NK_CR['parent']`) so the legal-disclosure pattern surfaces it. Suggested keys: `name`, `jurisdiction`, `reg_number`, `ein`, `registered_agent`, `registered_addr`, `email`.

Sweeps:

- [ ] All NTS LLC placeholders are now resolved (EIN 36-5148912 · WY filing 2025-001744917 · agent FBRA LLC · principal 1021 E Lincolnway Suite 8983 · phone +966 57 013 1122 · email support@novakeys.store). No further substitution needed before counsel review.
- [ ] Run `php tests/test-gift-card-matcher.php` (sanity — the legal pages aren't tested but the file lints).
- [ ] `scp` the updated `theme-bridge.php` to live (`/wp-content/plugins/novakeys-commerce/includes/theme/`).
- [ ] Visit `/terms/`, `/privacy/`, `/returns/`, `/warranty/`, `/usage/`, `/legal/` — confirm zero `ng-pending` chips remain and the NTS LLC parent row renders on `/legal/`.
- [ ] Remove the **B2** entry from the publish-readiness operator-blocked list.

## Operational follow-ups (separate work)

- Wire the **cookie-consent banner** referenced in Privacy §08. Today no banner is shown; PDPL Article 7 requires consent for non-essential cookies.
- Stand up `abuse@novakeys.store` and `security@novakeys.store` as monitored aliases (or forwarders to the support inbox). The Acceptable Use Policy promises both.
- Create a `/cookies/` page with the consent-management UI; link it from the footer.
- Confirm with the operator that the **10-year** retention on order records actually matches what the cPanel host's data-retention rules permit, and adjust if needed.
- **Sign and execute the internal DPA between the KSA controller (NovaKeys Store sole prop) and the US processor (NeoTechnology Solutions LLC, Wyoming).** The Privacy Policy promises this is in place; for PDPL Article 29(2)(b) "appropriate safeguards" to apply, the agreement actually has to exist and be signed. Keep a counter-signed copy on file.
- **Register NeoTechnology Solutions LLC as a transfer recipient** on any SDAIA disclosure portal that becomes operational, once SDAIA publishes the form.
