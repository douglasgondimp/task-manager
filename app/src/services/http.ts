import axios, { type AxiosInstance } from 'axios'

const http: AxiosInstance = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/api',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
})

export default http