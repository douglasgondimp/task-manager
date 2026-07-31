/**
 * @vitest-environment node
 */
import { describe, it, expect, vi, afterEach } from 'vitest'
import { createApp } from 'vue'
import {
    useAlert,
    useAlertProvider,
    AlertKey,
    type AlertType,
} from '@/composables/useAlert'

afterEach(() => {
    vi.restoreAllMocks()
})

describe('useAlertProvider', () => {
    it('initializes with default state', () => {
        const alert = useAlertProvider()

        expect(alert.show.value).toBe(false)
        expect(alert.type.value).toBe('error')
        expect(alert.message.value).toBe('')
    })

    it('showAlert updates type, message and shows the alert', () => {
        const alert = useAlertProvider()

        alert.showAlert('success', 'Projeto criado com sucesso.')

        expect(alert.show.value).toBe(true)
        expect(alert.type.value).toBe('success')
        expect(alert.message.value).toBe('Projeto criado com sucesso.')
    })

    it('hideAlert hides the alert and keeps the last message/type', () => {
        const alert = useAlertProvider()

        alert.showAlert('error', 'Erro ao carregar.')
        expect(alert.show.value).toBe(true)

        alert.hideAlert()

        expect(alert.show.value).toBe(false)
        // Message/type are kept so re-showing shows the same content
        expect(alert.message.value).toBe('Erro ao carregar.')
        expect(alert.type.value).toBe('error')
    })

    it('supports all alert types', () => {
        const alert = useAlertProvider()
        const messages: Record<AlertType, string> = {
            success: 'Sucesso',
            error: 'Erro',
            info: 'Info',
            warning: 'Atenção',
        }

        for (const [type, message] of Object.entries(messages)) {
            alert.showAlert(type as AlertType, message)

            expect(alert.show.value).toBe(true)
            expect(alert.type.value).toBe(type)
            expect(alert.message.value).toBe(message)
        }
    })

    it('showAlert can be called repeatedly overriding previous state', () => {
        const alert = useAlertProvider()

        alert.showAlert('warning', 'Primeira mensagem')
        alert.showAlert('info', 'Segunda mensagem')

        expect(alert.show.value).toBe(true)
        expect(alert.type.value).toBe('info')
        expect(alert.message.value).toBe('Segunda mensagem')
    })
})

describe('useAlert', () => {
    it('throws an error when no provider is available', () => {
        // Silence Vue's expected warning about inject() outside setup()
        vi.spyOn(console, 'warn').mockImplementation(() => { })

        expect(() => useAlert()).toThrow(/must be used within a component/)
    })

    it('returns the provided state from the app context', () => {
        const state = useAlertProvider()
        const app = createApp({ render: () => null })

        app.provide(AlertKey, state)

        const injected = app.runWithContext(() => {
            const alert = useAlert()
            expect(alert).toBe(state)
            return alert
        })

        expect(injected).toBe(state)
    })

    it('can trigger alerts through the injected state', () => {
        const state = useAlertProvider()
        const app = createApp({ render: () => null })

        app.provide(AlertKey, state)

        app.runWithContext(() => {
            const alert = useAlert()
            alert.showAlert('success', 'Alerta disparado')
        })

        expect(state.show.value).toBe(true)
        expect(state.type.value).toBe('success')
        expect(state.message.value).toBe('Alerta disparado')
    })
})