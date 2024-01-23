describe('Create workspaces', function() {
	before((browser) => {
		browser.loginAs('admin')
	})

	it('enters workspace application', (browser) => {
		browser
			.click('li[data-app-id=workspace]')
			.waitForElementVisible('div#content')
			.assert.titleContains('Workspace')
	})
	it('creates a workspace', async (browser) => {
		browser
			.workspace.create('new-espace-01')
			.workspace.create('second-espace-02') // create second in order to groupfolders table was present after removing one workpsace
			// TODO iterate list of workspaces and verify that they are in alphabetical order and created workspace is present
		const currentGroupfolders = await browser.groupfolders.currentNames()
		console.debug('currentGroupfolders ', currentGroupfolders)
		browser.expect(currentGroupfolders.indexOf('new-espace-01') !== -1).to.be.equal(true)
	})
})

describe('Remove a workspace', function() {
	it('removes a workspace', async (browser) => {
		browser
			.workspace.remove('new-espace-01')
		const currentGroupfolders = await browser.groupfolders.currentNames()
		console.debug('currentGroupfolders', currentGroupfolders)
		browser.expect(currentGroupfolders.indexOf('new-espace-01') === -1).to.be.equal(true)
	})
})
// ToDo: add "rename workspace" test function
