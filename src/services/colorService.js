/**
 * Generates a random hex color code.
 * @return {string} A random hex color code
 */
export function generateColor() {
	return '#' + Math.random().toString(16).slice(2, 8)
}
