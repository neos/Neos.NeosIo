import { test, expect } from '@playwright/test';

test('Blog feed is only accessible on root', async ({ page }) => {
    // Check whether the blog feed is accessible at the root URL
    const response = await page.request.get('/rss.xml');
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toBe('application/xml');
    expect(await response.text()).toMatch(/<rss version="2.0" xmlns:atom="http:\/\/www.w3.org\/2005\/Atom">/);

    // Check that the blog feed is not accessible on any other URL
    const response404 = await page.request.get('/some-other-page/rss.xml');
    expect(response404.status()).toBe(404);
});

test('Blog is accessible on /blog', async ({ page }) => {
    // Check whether the blog is accessible at the /blog URL
    const response = await page.request.get('/blog.html');
    expect(response.status()).toBe(200);
    expect(await response.text()).toContain('Stay up-to-date with Neos and Flow');
});

test('Individual blog posts are accessible', async ({ page }) => {
    // Check whether the blog is accessible at the /blog URL
    const response = await page.request.get('/blog/neos-and-flow-9-0-release.html');
    expect(response.status()).toBe(200);
    expect(await response.text()).toContain('Neos and Flow 9.0 Release');
});
