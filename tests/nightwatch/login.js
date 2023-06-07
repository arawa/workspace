describe('Login into nextcloud end-to-end test', function() {
	before(browser => browser.navigateTo('http://stable26.local/index.php'))

	it('arrives on the home page and submits credentials with login form', function(browser) {
		browser
			.waitForElementVisible('body')
			.assert.visible('input#user')
			.setValue('input#user', browser.globals.adminLogin)
			.setValue('input#password', browser.globals.adminPwd)
			.click('button[type=submit]')
	})
	it('verifies that workspace icon is present', function(browser) {
		browser
			.waitForElementPresent('a#nextcloud')
			.assert.elementPresent('li[data-app-id=workspace]')
	})
})
