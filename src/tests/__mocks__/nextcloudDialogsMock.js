import { vi } from 'vitest'

export const showError = vi.fn()
export const showSuccess = vi.fn()
export const showWarning = vi.fn()
export const getFilePickerBuilder = vi.fn(() => ({
	setMultiSelect: vi.fn().mockReturnThis(),
	setType: vi.fn().mockReturnThis(),
	allowDirectories: vi.fn().mockReturnThis(),
	build: vi.fn(),
}))
