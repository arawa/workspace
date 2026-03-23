import 'core-js/stable/index.js'
/* eslint-disable-next-line */
import 'regenerator-runtime/runtime.js'
/* eslint-disable-next-line */
import { OC } from './OC.js'
import { config } from '@vue/test-utils'
import mockAxios from 'jest-mock-axios'

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
	error: jest.fn(),
	debug: jest.fn(),
}

global.appName = 'workspace'

global.OCA = {}

jest.mock('@nextcloud/axios', () => mockAxios)
