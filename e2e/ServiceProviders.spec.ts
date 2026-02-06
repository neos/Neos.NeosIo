import { test, expect } from '@playwright/test';

test('Service provider list is accessible', async ({ page }) => {
    const response = await page.request.get('/who-uses-neos/service-providers.html');
    expect(response.status()).toBe(200);
    expect(await response.text()).toContain('Neos CMS &amp; Flow Service Providers');
});

test('A service provider detail page is accessible', async ({ page }) => {
    const response = await page.request.get('/who-uses-neos/service-providers/sebastian-helzle-it-beratung.html');
    expect(response.status()).toBe(200);
    expect(await response.text()).toContain('Sebastian Helzle IT-Beratung');
});
