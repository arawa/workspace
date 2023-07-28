/**
 * A non-class-based custom-command in Nightwatch. The command name is the filename.
 *
 * Usage:
 *   browser.loginAs(role, browser)
 * This command is not used yet used in any of the examples.
 *
 * For more information on working with custom-commands see:
 *   https://nightwatchjs.org/guide/extending-nightwatch/adding-custom-commands.html
 *
 */

module.exports = {
	command(role) {
	  const login = role === 'admin' ? this.globals.adminLogin : ''
	  const pwd = role === 'admin' ? this.globals.adminPwd : ''
    //   console.log('this.api.launchUrl ', this.api.launchUrl)
	  this.navigateTo('http://stable26.local/index.php')
	  this
			.waitForElementVisible('body')
			.setValue('input#user', login)
			.setValue('input#password', pwd)
			.click('button[type=submit]')
			.waitForElementPresent('a#nextcloud')
	},
}
