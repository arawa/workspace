import { vi } from 'vitest'

// Like jest-mock-axios: an unmocked request stays pending instead of returning undefined
const pendingRequest = () => new Promise(() => {})

const axios = vi.fn(pendingRequest)
axios.get = vi.fn(pendingRequest)
axios.post = vi.fn(pendingRequest)
axios.put = vi.fn(pendingRequest)
axios.patch = vi.fn(pendingRequest)
axios.delete = vi.fn(pendingRequest)

export default axios
