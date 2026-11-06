# Search deployment checks

The canonical origin and sitemap use `https://annaaccesoriinunta.md`. Keep `PUBLIC_SITE_URL` for order emails on that same HTTPS origin. The document root must be `web/`.

## Hosting

Configure IPHOST.md or its trusted edge proxy to redirect HTTP to HTTPS and `www.annaaccesoriinunta.md` to `annaaccesoriinunta.md`. Preserve the path and query string. Use permanent redirects for public GET pages and method-preserving redirects for form submissions. Do this at the hosting layer, where the original client scheme is known; do not trust arbitrary forwarded-protocol headers in PHP. Keep localhost and staging redirects separate.

Verify both host variants and the redirect chain with `curl -I`, including a product URL and a query-string URL. Confirm there is one final canonical tag and no redirect loop. Check that the production environment uses `APP_ENV=prod` and that its public pages do not contain `noindex`.

## Search Console

1. Add a Domain property for `annaaccesoriinunta.md` in Google Search Console and follow its DNS TXT verification instructions. Do not place a guessed verification token in source code.
2. Submit `https://annaaccesoriinunta.md/sitemap.xml` and confirm `https://annaaccesoriinunta.md/robots.txt` references it.
3. Inspect the homepage, catalog page 2, and several product URLs. Check indexing eligibility, rendered canonical URL, mobile usability, and image loading. Inspect a deleted product URL to confirm HTTP 404.
4. Validate a real product page with Google's Rich Results Test. The markup contains actual name, description, primary image, price, and MDL currency; it intentionally omits inventory availability, ratings, and shipping claims.
5. Measure Core Web Vitals on representative mobile pages after deployment. Review large product images and hosting cache/compression settings before changing assets.

The `/produse?category=...` filters and internal search results remain `noindex` because they do not have distinct category landing content. The unfiltered catalog pagination has crawlable links and a canonical URL for each page. Add dedicated category landing pages only after writing useful, original category content.

Search Console reporting and ranking changes require time and external evidence; code changes alone cannot guarantee positions.
