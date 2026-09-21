export const showError = jest.fn()
export const showSuccess = jest.fn()
export const showWarning = jest.fn()
export const getFilePickerBuilder = jest.fn(() => ({
	setMultiSelect: jest.fn().mockReturnThis(),
	setType: jest.fn().mockReturnThis(),
	allowDirectories: jest.fn().mockReturnThis(),
	build: jest.fn(),
}))
