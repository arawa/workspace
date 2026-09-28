/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import { recommendedJavascript } from '@nextcloud/eslint-config'
import globals from 'globals'

export default [
	...recommendedJavascript,
	{
		files: ['src/tests/**'],
		languageOptions: {
			globals: globals.jest,
		},
	},
	// @nextcloud/eslint-config 9 is stricter than 8 and enforces a new code style.
	// Until the codebase is migrated to it, keep the level of @nextcloud/eslint-config 8:
	// same settings for the rules it already had, new rules disabled.
	{
		files: ['**/*.js', '**/*.vue'],
		rules: {
			'no-console': ['error', { allow: ['error', 'warn', 'info', 'debug'] }],
			'no-unused-vars': ['error', { args: 'none', caughtErrors: 'none', ignoreRestSiblings: true, vars: 'all' }],
			'@stylistic/indent': ['error', 'tab', { SwitchCase: 0 }],
			'@stylistic/padded-blocks': ['error', { classes: 'always' }],
			'@stylistic/comma-dangle': ['warn', 'always-multiline'],

			'@nextcloud/no-deprecated-library-props': 'off',
			'@stylistic/arrow-parens': 'off',
			'@stylistic/exp-list-style': 'off',
			'@stylistic/function-paren-newline': 'off',
			'@stylistic/implicit-arrow-linebreak': 'off',
			'@stylistic/indent-binary-ops': 'off',
			'antfu/top-level-function': 'off',
			'no-useless-assignment': 'off',
			'perfectionist/sort-imports': 'off',
			'perfectionist/sort-named-imports': 'off',
			'prefer-object-has-own': 'off',
		},
	},
	{
		// Documentation rules, which @nextcloud/eslint-config does not apply to tests
		files: ['**/*.js', '**/*.vue'],
		ignores: ['src/tests/**'],
		rules: {
			'jsdoc/check-tag-names': ['warn', { definedTags: ['jest-environment'] }],
		},
	},
	{
		files: ['**/*.vue'],
		rules: {
			// TODO: Search how to config it
			// https://eslint.vuejs.org/rules/first-attribute-linebreak.html
			'vue/first-attribute-linebreak': 'off',
			'vue/multi-word-component-names': 'off',

			'vue/attribute-hyphenation': 'warn',
			'vue/custom-event-name-casing': ['error', 'kebab-case', { ignores: ['/^[a-z]+(?:-[a-z]+)*:[a-z]+(?:-[a-z]+)*$/u'] }],
			'vue/max-attributes-per-line': ['warn', { singleline: 3, multiline: 1 }],

			'vue/comma-spacing': 'off',
			'vue/new-line-between-multi-line-property': 'off',
			'vue/no-deprecated-slot-attribute': 'off',
			'vue/no-empty-component-block': 'off',
			'vue/no-required-prop-with-default': 'off',
			'vue/no-useless-v-bind': 'off',
			'vue/padding-line-between-blocks': 'off',
			'vue/prefer-separate-static-class': 'off',
			'vue/space-infix-ops': 'off',
			'vue/v-on-event-hyphenation': 'off',
		},
	},
]
