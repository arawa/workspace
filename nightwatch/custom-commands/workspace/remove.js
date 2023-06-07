/**
 * function style Nightwatch command
 * goes to /apps/workspace/workspace/spaceName and clicks 'Delete workspace' icon
 *
 * @param {string} spaceName workspace name
 */
module.exports = {
	async command(spaceName) {
		this
			.navigateTo(`http://stable26.local/index.php/apps/workspace/workspace/${spaceName}`)
			.waitForElementPresent('ul.app-navigation__list')
		const toggleMenuBtn = await this.findElements('button.action-item__menutoggle')
		await this.click(toggleMenuBtn[1], function(result) {
			console.debug('Click result', result)
		  })
		this.waitForElementPresent('ul[role=menu]')
			.within('ul[role=menu]').click('span.icon-delete', function(result) {
				console.debug('Click result', result)
			  })
		this.waitForElementPresent('.modal__content')
		const deleteBtn = await this.findElements('.remove-space-actions button')
		console.debug('deleteBtn ', deleteBtn[1])
		this.click(deleteBtn[1], function(result) {
			console.debug('Click result', result)
		  })
		this.waitForElementNotPresent('.modal__content')
	},
}
