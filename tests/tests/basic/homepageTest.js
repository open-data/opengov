module.exports = {
    '@tags': ['homepage'],
    'Homepage' : function (browser) {
        browser.url(browser.launch_url)
            .waitForElementVisible('body')
            .assert.containsText('body', 'Open Government')
            .end();
    }
}
