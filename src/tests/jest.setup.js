import 'core-js/stable/index.js'
/* eslint-disable-next-line */
import 'regenerator-runtime/runtime.js'
/* eslint-disable-next-line */
import { OC } from './OC.js'
import { config } from '@vue/test-utils'
import mockAxios from 'jest-mock-axios'

document.title = 'Standard Nextcloud title'

// Mock nextcloud translate functions
config.mocks.$t = function(app, string) {
	return string
}

config.mocks.t = config.mocks.$t
global.t = config.mocks.$t

config.mocks.$n = function(app, singular, plural, count) {
	return singular
}
config.mocks.n = config.mocks.$n
global.n = config.mocks.$n

global.console = {
	...console,
	error: jest.fn(),
	debug: jest.fn(),
}

global.OCA = {}

jest.mock('@nextcloud/axios', () => mockAxios)
