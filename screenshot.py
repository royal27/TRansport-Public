from playwright.sync_api import sync_playwright

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    page = browser.new_page(viewport={'width': 1280, 'height': 800})
    page.goto('http://localhost:8000')
    page.wait_for_timeout(2000)
    page.screenshot(path='screenshot_bottom_bar.png')
    browser.close()
