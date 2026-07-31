import { inject, ref, type Ref } from 'vue'

export type AlertType = 'success' | 'error' | 'info' | 'warning'

export interface AlertState {
    show: Ref<boolean>
    type: Ref<AlertType>
    message: Ref<string>
    showAlert: (type: AlertType, message: string) => void
    hideAlert: () => void
}

const AlertKey = Symbol('apiAlert')

export function useAlert(): AlertState {
    const state = inject<AlertState>(AlertKey)

    if (!state) {
        throw new Error(
            'useApiAlert must be used within a component that has the ApiAlertProvider',
        )
    }

    return state
}

export function useAlertProvider(): AlertState {
    const show = ref(false)
    const type = ref<AlertType>('error')
    const message = ref('')

    function showAlert(alertType: AlertType, alertMessage: string) {
        type.value = alertType
        message.value = alertMessage
        show.value = true
    }

    function hideAlert() {
        show.value = false
    }

    return {
        show,
        type,
        message,
        showAlert,
        hideAlert,
    }
}

export { AlertKey }
