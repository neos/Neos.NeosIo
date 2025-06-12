import { test, expect } from '@playwright/test';

test('Sitemap is only accessible on root', async ({ page }) => {
    // Check whether the sitemap is accessible at the root URL
    const response = await page.request.get('/sitemap.xml');
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toBe('text/xml;charset=UTF-8');
    expect(await response.text()).toMatch(/<urlset xmlns="http:\/\/www\.sitemaps\.org\/schemas\/sitemap\/0\.9".*>/);

    // Check that the sitemap is not accessible on any other URL
    const response404 = await page.request.get('/some-other-page/sitemap.xml');
    expect(response404.status()).toBe(404);
});

test('Robots.txt is only accessible on root', async ({ page }) => {
    // Check whether the robots.txt is accessible at the root URL
    const response = await page.request.get('/robots.txt');
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toBe('text/plain;;charset=UTF-8');
    expect(await response.text()).toMatch(/User-agent: \*/);

    // Check that the robots.txt is not accessible on any other URL
    const response404 = await page.request.get('/some-other-page/robots.txt');
    expect(response404.status()).toBe(404);
});

test('Blog feed is only accessible on root', async ({ page }) => {
    // Check whether the blog feed is accessible at the root URL
    const response = await page.request.get('/rss.xml');
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toBe('application/xml');
    expect(await response.text()).toMatch(/<rss version="2.0" xmlns:atom="http:\/\/www.w3.org\/2005\/Atom">/);

    // Check that the blog feed is not accessible on any other URL
    const response404 = await page.request.get('/some-other-page/rss.xml');
    expect(response404.status()).toBe(404);
})

test('Package json feed is only accessible on package repository', async ({ page }) => {
    // Check whether the package.json feed is accessible at the package repository URL
    const response = await page.request.get('/download-and-extend/packages.json');
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toBe('application/json');
    expect(await response.text()).toMatch(/"results"/);

    // Check that the package.json feed is not accessible on any other URL
    const response404 = await page.request.get('/packages.json');
    expect(response404.status()).toBe(404);
});

test('Package xml feed is only accessible on package repository', async ({ page }) => {
    // Check whether the package.xml feed is accessible at the package repository URL
    const response = await page.request.get('/download-and-extend/packages.atom');
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toBe('application/xml');
    expect(await response.text()).toMatch(/<\?xml version="1.0" encoding="utf-8"\?>/);

    // Check that the package.xml feed is not accessible on any other URL
    const response404 = await page.request.get('/packages.atom');
    expect(response404.status()).toBe(404);
});
