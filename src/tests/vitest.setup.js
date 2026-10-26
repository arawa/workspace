import './OC.js'
import { config } from '@vue/test-utils'
import { vi } from 'vitest'

document.title = 'Standard Nextcloud title'

// Mock nextcloud translate functions
config.global.mocks.$t = function(app, string) {
	return string
}

config.global.mocks.t = config.global.mocks.$t
global.t = config.global.mocks.$t

config.global.mocks.$n = function(app, singular, plural, count) {
	return singular
}
config.global.mocks.n = config.global.mocks.$n
global.n = config.global.mocks.$n

global.console = {
	...console,
	error: vi.fn(),
	debug: vi.fn(),
}

global.appName = 'workspace'

global.OCA = {}
