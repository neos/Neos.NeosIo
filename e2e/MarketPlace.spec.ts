import { test, expect } from '@playwright/test';

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

test('Package repository is accessible', async ({ page }) => {
    const response = await page.request.get('/download-and-extend/packages.html');
    expect(response.status()).toBe(200);
    expect(await response.text()).toContain('Neos CMS core &amp; community packages');
});
