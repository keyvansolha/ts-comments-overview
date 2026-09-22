# TS Comments Overview — خلاصه نظرات کاربران محصولات

بخش «آنچه کاربران درباره این محصول می‌گویند» را بالای فهرست نظرات صفحه‌ی محصول
تهران‌اسپیکر نمایش می‌دهد. داده از سرویس تحلیل نظرات (mytsapp.ir) می‌آید که نظرات
کاربران در چند سایت مرجع را می‌خواند و خلاصه‌ی آن‌ها را برمی‌گرداند.

**English:** Displays an automatically generated "what users say" summary of a
product's external user reviews at the top of the product comment section,
sourced from the mytsapp.ir comments-analysis service. Off by default, three
display modes, token read from `wp-config.php`, all styling built from the
Amazing theme's semantic design tokens (light + dark).

---

## چرا افزونه و نه قالب؟

- حالت نمایش با یک گزینه در پیشخوان روشن/خاموش می‌شود (بدون دست زدن به کد قالب).
- توکن سرویس در `wp-config.php` می‌ماند، نه در دیتابیس.
- با غیرفعال کردن افزونه، قالب دقیقاً به حالت قبل برمی‌گردد: قالب فقط یک هوک صدا
  می‌زند و هیچ وابستگی مستقیمی به کلاس‌های افزونه ندارد.

---

## نصب و راه‌اندازی

1. پوشه‌ی `ts-comments-overview` را در `wp-content/plugins/` قرار دهید و افزونه را
   از پیشخوان فعال کنید.
2. توکن سرویس را در `wp-config.php` بگذارید:

```php
// توکن سرویس تحلیل نظرات (افزونه ts-comments-overview) — هدر X-API-Token.
define( 'TS_COMMENTS_OVERVIEW_API_TOKEN', 'توکن-واقعی' );
```

3. به **تنظیمات ← خلاصه نظرات محصولات** بروید و حالت نمایش را انتخاب کنید.
4. چون صفحات محصول با WP Rocket کش می‌شوند، بعد از روشن کردن حالت (و هر بار تغییر
   حالت) کش صفحات را پاک کنید تا صفحات محصول دوباره با بخش خلاصه ساخته شوند.
   داده‌ی سرویس هم به‌صورت ترنزینت کش می‌شود؛ پاک‌سازی آن از همان صفحه‌ی تنظیمات
   انجام می‌شود.

> تا وقتی توکن تنظیم نشده باشد، بخش خلاصه در سایت نمایش داده **نمی‌شود** و در
> پیشخوان یک هشدار می‌بینید. این کار عمدی است تا با توکن جعلی، درخواست بی‌فایده
> به سرویس فرستاده نشود.

---

## حالت‌های نمایش

| گزینه | رفتار |
| --- | --- |
| خاموش | هیچ بخشی رندر نمی‌شود؛ هیچ درخواستی به سرویس زده نمی‌شود. |
| روشن (خلاصه) | فقط خلاصه‌ی نظرات، آمار، نوار احساسات و موضوع‌ها. |
| روشن + نقاط قوت و ضعف | همان خلاصه به‌همراه دو پنل «نقاط قوت» و «نقاط ضعف» از فیلدهای `strengths` و `weaknesses`. |

اگر برای محصولی `strengths`/`weaknesses` خالی باشد، همان حالت سوم هم فقط خلاصه را
نشان می‌دهد (پنل خالی ساخته نمی‌شود). مقدار پیش‌فرض افزونه «خاموش» است.

---

## اتصال به سرویس

```
GET https://mytsapp.ir/api/external/comments-analysis/?woocommerce_id=<product-id>
Header: X-API-Token: <token>
```

- شناسه‌ی ارسالی، شناسه‌ی **محصول والد** است (`$product->get_id()`)، چون خلاصه در
  سطح محصول معنا دارد.
- پاسخ می‌تواند مستقیم رکورد باشد یا داخل `data` / `result` / `results`.
- کلیدهای camelCase و snake_case هر دو پذیرفته می‌شوند (`overallRating` و
  `overall_rating`، `sourceCommentCount` و `sourcecommentcount` و …).

### کش

هر تحلیل تا **۶ ساعت** در ترنزینت نگه داشته می‌شود (یک درخواست به‌ازای هر محصول در
هر بازه). خطاها هم **۱۵ دقیقه** کش می‌شوند تا سرویس خواب‌رفته، رندر صفحه‌ی محصول
را کند نکند. پاک‌سازی کش از همان صفحه‌ی تنظیمات (یک محصول یا همه) انجام می‌شود.

اگر پاسخ سرویس هیچ محتوایی نداشته باشد (نه خلاصه، نه قوت/ضعف، نه احساسات)، بخش
خلاصه بی‌صدا نمایش داده نمی‌شود؛ هیچ پیام خطایی روی سایت چاپ نمی‌شود.

---

## یکپارچگی با قالب (هوک)

قالب Amazing در دو فایل، پیش از فهرست نظرات، این هوک را صدا می‌زند:

```php
// lib/Product/template/desktop/comments.php
// lib/Product/template/mobile/panels/comments.php
do_action( 'wbs_product_comments_overview', $product );
```

نام هوک با ثابت `TS_COMMENTS_OVERVIEW_HOOK` (پیش از بارگذاری افزونه) قابل
بازنویسی است. صفحه‌ی تنظیمات بررسی می‌کند که قالب فعال این هوک را صدا می‌زند یا
نه و نتیجه را نشان می‌دهد.

---

## UI/UX

- همه‌ی رنگ‌ها از توکن‌های معنایی قالب (`assets/css/theme-system.css`) می‌آیند:
  `--surface-*`, `--text-*`, `--border-*`, `--theme-accent`, `--state-*`.
  هیچ رنگ ثابتی در CSS افزونه نیست؛ به همین دلیل دارک و لایت خودکار درست است و
  هیچ قاعده‌ی جداگانه‌ای برای `body.dark` نوشته نشده.
- استایل با وابستگی به `amazing-theme-system` صف‌بندی می‌شود تا همیشه بعد از لایه‌ی
  توکن‌ها بارگذاری شود، و فقط در صفحه‌ی محصول و فقط وقتی بخش فعال است.
- ساختار داخل خود پنل نظرات (`.comments-panel`) قرار می‌گیرد، بین هدر بخش نظرات و
  فهرست نظرات؛ بنابراین همان چیدمان و فاصله‌های سایت را ادامه می‌دهد.
- آیکون‌ها از فونت آیکون خود قالب‌اند (`icon-chat`, `icon-star-empty`, `icon-like`,
  `icon-dislike`, `icon-categories`, `icon-global`, `icon-clock`, `icon-history`).
- RTL کامل با ویژگی‌های منطقی CSS (`padding-inline-start`, `margin-inline-start`).
  موبایل: آمار و پنل‌های قوت/ضعف تک‌ستونه می‌شوند.
- نوار احساسات با `flex-grow` بر پایه‌ی شمارش خام تقسیم می‌شود تا جمع سهم‌ها همیشه
  دقیقاً ۱۰۰٪ باشد و ته نوار شکاف خالی نماند.
- سلب مسئولیت زیر بخش ذکر می‌شود که این خلاصه خودکار است و نظر تهران‌اسپیکر نیست.

---

## امنیت

- توکن فقط از `wp-config.php` خوانده می‌شود؛ نه در آپشن‌ها ذخیره می‌شود، نه در کش،
  نه در HTML پیشخوان چاپ می‌شود. فقط «تنظیم شده / نشده + تعداد کاراکتر» گزارش می‌شود.
- درخواست با `wp_remote_get`، مهلت ۸ ثانیه، سقف ۵۱۲KB و بدون دنبال کردن ریدایرکت.
- نشانی سرویس فقط `https` پذیرفته می‌شود.
- همه‌ی رشته‌های سرویس با `wp_strip_all_tags` تمیز و با `esc_html` چاپ می‌شوند.
  خلاصه به‌صورت متن ساده با قالب‌بندی حداقلی و امن رندر می‌شود (escape پیش از ساخت
  تگ؛ فقط فهرست و `**پررنگ**`). هیچ HTML از سرویس اجرا نمی‌شود.
- اعداد در بازه‌ی منطقی خود محدود می‌شوند (امتیاز ۰..۵، درصد ۰..۱۰۰، شمارش‌ها ≥ ۰).
  ارقام فارسی/عربی و پسوند `٪` هم خوانده می‌شوند.

---

## تنظیمات از wp-config.php (همه اختیاری)

```php
define( 'TS_COMMENTS_OVERVIEW_API_TOKEN', '...' );            // الزامی برای نمایش
define( 'TS_COMMENTS_OVERVIEW_API_ENDPOINT', 'https://...' ); // پیش‌فرض: mytsapp.ir
define( 'TS_COMMENTS_OVERVIEW_HOOK', 'wbs_product_comments_overview' );
define( 'TS_COMMENTS_OVERVIEW_CACHE_TTL', 21600 );            // ۶ ساعت
define( 'TS_COMMENTS_OVERVIEW_ERROR_TTL', 900 );              // ۱۵ دقیقه
```

فیلترها: `ts_comments_overview_cache_ttl`, `ts_comments_overview_error_ttl`,
`ts_comments_overview_api_endpoint`, `ts_comments_overview_api_token` (فقط تست).

---

## تست

```bash
# تست‌های PHP افزونه (بدون نیاز به نصب وردپرس؛ توابع وردپرس شبیه‌سازی می‌شوند)
php tests/run.php

# تست نگهبان سمت قالب: توکن‌های طراحی + هوک + escape
cd ../../themes/amazing && node --test tests/comments-overview-integration.test.mjs
```

تست‌های PHP واقعاً کد افزونه را اجرا می‌کنند: سه حالت نمایش، کش، کش خطا، خطاهای
HTTP، پیلود مخرب (XSS)، محدودسازی اعداد، سه گزینه‌ی پیشخوان و عدم افشای توکن.

---

## ساختار فایل‌ها

```
ts-comments-overview.php                            هدر، ثابت‌ها، راه‌اندازی
includes/class-ts-comments-overview-settings.php    حالت نمایش، توکن، نشانی، TTL
includes/class-ts-comments-overview-payload.php     نرمال‌سازی و پاکسازی پاسخ سرویس
includes/class-ts-comments-overview-api.php         کلاینت HTTP + کش
includes/class-ts-comments-overview-render.php      رندر بخش و هوک قالب
includes/class-ts-comments-overview-admin.php       صفحه‌ی تنظیمات، بررسی زنده، پاک‌سازی کش
templates/section.php                               مارک‌آپ بخش
assets/css/comments-overview.css                    استایل مبتنی بر توکن‌های قالب
tests/                                              بستر تست PHP بدون وردپرس
```

---

## ابزار بررسی زنده در پیشخوان

در صفحه‌ی تنظیمات، با دادن شناسه‌ی یک محصول می‌توانید پاسخ واقعی سرویس را ببینید:
هم داده‌ی نرمال‌شده‌ای که در سایت چاپ می‌شود، هم JSON خام. این درخواست کش نمی‌شود و
برای تطبیق ساختار پیلود سرویس با نمایش سایت است.
