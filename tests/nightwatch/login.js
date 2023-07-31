describe('Chek if the workspace app is present', function() {
	before(browser => browser.navigateTo(browser.launchUrl))

	it('login as admin', function(browser) {
		browser
			.waitForElementVisible('body')
			.assert.visible('input#user')
			.setValue('input#user', browser.globals.adminLogin)
			.setValue('input#password', browser.globals.adminPwd)
			.click('button[type=submit]')
	})
	
	it('check that workspace icon is present', function(browser) {
		browser
			.waitForElementPresent('a#nextcloud')
			.assert.elementPresent('li[data-app-id=workspace]')
	})
})
