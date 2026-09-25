<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
 xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
 xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9"
 xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
 exclude-result-prefixes="s image">
<xsl:output method="html" encoding="UTF-8"/>
<xsl:template match="/">
<html><head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>XML Sitemap</title>
<style>
body{margin:0;background:#f5f7fa;color:#273142;font:14px -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif}main{max-width:1240px;margin:28px auto;padding:0 18px}.box{background:#fff;border:1px solid #e1e6ec;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(31,41,55,.05)}header{padding:18px 20px;border-bottom:1px solid #e1e6ec}h1{font-size:22px;margin:0 0 5px}p{margin:0;color:#667085}.count{display:inline-block;margin-left:7px;padding:2px 7px;border-radius:12px;background:#eaf3fb;color:#0b6fd3;font-size:12px}table{width:100%;border-collapse:collapse}th,td{padding:10px 12px;border-bottom:1px solid #edf0f3;text-align:left;vertical-align:top}th{background:#f8fafc;font-size:12px;text-transform:uppercase;color:#667085}tr:last-child td{border-bottom:0}a{color:#0b6fd3;text-decoration:none;word-break:break-all}a:hover{text-decoration:underline}.date{white-space:nowrap;color:#667085}.img{font-size:12px;color:#667085}@media(max-width:700px){main{margin:12px auto;padding:0 8px}th:nth-child(2),td:nth-child(2){display:none}th,td{padding:8px}.box{border-radius:7px}}
</style></head><body><main><div class="box">
<header><h1>XML Sitemap <span class="count"><xsl:value-of select="count(s:sitemapindex/s:sitemap | s:urlset/s:url)"/> URL</span></h1><p>Machine-readable sitemap with a browser-friendly view. Search engines receive the same XML data.</p></header>
<xsl:choose>
<xsl:when test="s:sitemapindex"><table><thead><tr><th>Sitemap</th><th>Last modified</th></tr></thead><tbody><xsl:for-each select="s:sitemapindex/s:sitemap"><tr><td><a href="{s:loc}"><xsl:value-of select="s:loc"/></a></td><td class="date"><xsl:value-of select="s:lastmod"/></td></tr></xsl:for-each></tbody></table></xsl:when>
<xsl:otherwise><table><thead><tr><th>URL</th><th>Last modified</th><th>Images</th></tr></thead><tbody><xsl:for-each select="s:urlset/s:url"><tr><td><a href="{s:loc}"><xsl:value-of select="s:loc"/></a></td><td class="date"><xsl:value-of select="s:lastmod"/></td><td class="img"><xsl:value-of select="count(image:image)"/></td></tr></xsl:for-each></tbody></table></xsl:otherwise>
</xsl:choose>
</div></main></body></html>
</xsl:template></xsl:stylesheet>
