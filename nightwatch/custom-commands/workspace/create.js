module.exports = {
	command(spaceName) {
		this
			.click('div.icon-add')
			.assert.elementPresent('input[type=text]')
			.setValue('input[type=text]', spaceName)
			.click('button[type=submit]')
			.waitForElementPresent('ul.app-navigation__list li')
	},
}
