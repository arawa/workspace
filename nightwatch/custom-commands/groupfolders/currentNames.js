async function getGroupfolderName(browser, index) {
	return await browser.getText({
		selector: 'tbody tr td:nth-child(1) a',
		index,
	})
}
/**
 *  class-based custom-command in Nightwatch. The command name is the filename
 *
 * Usage:
 *   browser.currentNames()
 * Goes to the groupfolders page and returns the list of created groupfolders names
 *  The browser object is available via this.api.
 *
 * For more information on working with custom-commands see:
 *   https://nightwatchjs.org/guide/extending-nightwatch/adding-custom-commands.html
 *
 */
module.exports = class CurrentNames {

	async command() {
	  this.api.navigateTo('http://stable26.local/index.php/settings/admin/groupfolders')
	  this.api
			.waitForElementVisible('#groupfolders-wrapper')
			.assert.elementPresent('table')
		try {
			const groupfolders = await this.api.findElements('tbody tr td:nth-child(1) a')
			const gfNamesPromises = await groupfolders.map((_gf, i) => {
				return getGroupfolderName(this.api, i)
			})
			return await Promise.all(gfNamesPromises)
		} catch (err) {
			console.debug('currentNames error ', err)
			return []
		}
	}

}
