<template>
  <form class="card border-0 shadow-sm" @submit.prevent="submitForm">
    <div class="card-header bg-white py-3">
      <h1 class="h5 mb-0">Фильтр задач</h1>
    </div>

    <div class="card-body">
      <div v-if="apiError" class="alert alert-danger" role="alert">
        {{ apiError }}
      </div>

      <div v-if="downloadUrl" class="alert alert-success" role="status">
        Файл должен быть готов.
        <a :href="downloadUrl" target="_blank" rel="noopener noreferrer">Скачать Excel</a>
        <p>
          Убедитесь, что браузер залогинен под пользователя в <a
          href="https://tracker.yandex.ru"
          target="_blank"
          rel="noopener noreferrer">Яндекс Трекере</a>
        </p>
      </div>

      <div v-if="waitSecondsLeft > 0" class="alert alert-info d-flex gap-2" role="status">
        <span class="spinner-border spinner-border-sm mt-1" aria-hidden="true" />
        <span>
          Отчет формируется в Яндексе. Ссылка появится через {{ waitSecondsLeft }} сек.
        </span>
      </div>

      <div class="mb-3">
        <label class="form-label" for="statusDropdown">Статусы</label>
        <div class="dropdown">
          <button
            id="statusDropdown"
            class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center justify-content-between w-100"
            type="button"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
          >
            <span class="text-truncate">{{ selectedStatusesLabel }}</span>
          </button>

          <ul class="dropdown-menu w-100">
            <li v-for="status in statusOptions" :key="status.value">
              <label class="dropdown-item">
                <input
                  v-model="form.statuses"
                  class="form-check-input me-2"
                  type="checkbox"
                  :value="status.value"
                />
                {{ status.label }}
              </label>
            </li>
          </ul>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="deadline">Дедлайн</label>
        <input
          id="deadline"
          v-model="form.deadline"
          class="form-control"
          type="date"
          name="deadline"
        />
      </div>

      <div class="mb-3">
        <span class="form-label">Оценка, часы</span>
        <div class="row g-2">
          <div class="col-12 col-sm">
            <label class="form-label visually-hidden" for="estimateFrom">Оценка от</label>
            <div class="input-group">
              <span class="input-group-text">От</span>
              <input
                id="estimateFrom"
                v-model="form.estimateFrom"
                class="form-control"
                type="number"
                aria-label="Оценка от"
                name="estimateFrom"
                min="0"
                step="0.5"
              />
              <span class="input-group-text">ч</span>
            </div>
          </div>
          <div class="col-12 col-sm">
            <label class="form-label visually-hidden" for="estimateTo">Оценка до</label>
            <div class="input-group">
              <span class="input-group-text">До</span>
              <input
                id="estimateTo"
                v-model="form.estimateTo"
                class="form-control"
                type="number"
                aria-label="Оценка до"
                name="estimateTo"
                min="0"
                step="0.5"
              />
              <span class="input-group-text">ч</span>
            </div>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label" for="actualHours">Реальные часы</label>
        <div class="input-group">
          <input
            id="actualHours"
            v-model="form.actualHours"
            class="form-control"
            type="number"
            name="actualHours"
            min="0"
            step="0.5"
          />
          <span class="input-group-text">ч</span>
        </div>
      </div>

      <button class="btn btn-primary w-100" type="submit" :disabled="isBusy">
        <span
          v-if="isBusy"
          class="spinner-border spinner-border-sm me-2"
          aria-hidden="true"
        />
        {{ submitButtonText }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue'

const API_URL = 'http://localhost:8000/api'
const EXCEL_EXPORT_PATH = '/ticket/export-xlsx'
const REPORT_WAIT_SECONDS = 10

const ATTACHMENT_BASE_URL_HEAD = 'https://tracker.yandex.ru/ajax/v2/metaEntities/reports/'
const ATTACHMENT_BASE_URL_TAIL = '/attachments'

const statusOptions = [
  { value: 'backlog', label: 'Беклог' },
  { value: 'open', label: 'Открыт' },
  { value: 'inProgress', label: 'В работе' },
  { value: 'closed', label: 'Закрыт' },
  { value: 'cancelled', label: 'Отменено' },
]

const form = reactive({
  statuses: [],
  deadline: '',
  estimateFrom: '',
  estimateTo: '',
  actualHours: '',
})

const apiError = ref('')
const downloadUrl = ref('')
const isLoading = ref(false)
const waitSecondsLeft = ref(0)
let waitTimer = null

const selectedStatusesLabel = computed(() => {
  if (!form.statuses.length) {
    return 'Любые статусы'
  }

  return statusOptions
    .filter((status) => form.statuses.includes(status.value))
    .map((status) => status.label)
    .join(', ')
})

const isBusy = computed(() => isLoading.value || waitSecondsLeft.value > 0)

const submitButtonText = computed(() => {
  if (isLoading.value) {
    return 'Запрашиваем отчет...'
  }

  if (waitSecondsLeft.value > 0) {
    return 'Ждем файл...'
  }

  return 'Выгрузить в Excel'
})

function buildQueryParams() {
  const params = new URLSearchParams()

  form.statuses.forEach((status) => params.append('statuses[]', status))

  Object.entries({
    deadline: form.deadline,
    evaluation_min: form.estimateFrom,
    evaluation_max: form.estimateTo,
    actual_amount_of_hours: form.actualHours,
  }).forEach(([key, value]) => {
    if (value !== '') {
      params.append(key, value)
    }
  })

  return params
}

function getExcelExportApiUrl() {
  const query = buildQueryParams().toString()
  const url = `${API_URL}${EXCEL_EXPORT_PATH}`

  return query ? `${url}?${query}` : url
}

async function parseApiResponse(response) {
  const text = await response.text()

  if (!text) {
    return null
  }

  try {
    return JSON.parse(text)
  } catch {
    return text
  }
}

function getAttachmentId(data) {
  if (typeof data === 'string' || typeof data === 'number') {
    return data
  }

  return data?.id || data?.attachmentId || data?.fileId
}

function buildAttachmentUrl(id) {
  return `${ATTACHMENT_BASE_URL_HEAD}${encodeURIComponent(id)}${ATTACHMENT_BASE_URL_TAIL}`
}

function clearWaitTimer() {
  if (waitTimer) {
    clearInterval(waitTimer)
    waitTimer = null
  }

  waitSecondsLeft.value = 0
}

function waitBeforeShowingDownload(url) {
  clearWaitTimer()
  waitSecondsLeft.value = REPORT_WAIT_SECONDS

  waitTimer = setInterval(() => {
    waitSecondsLeft.value -= 1

    if (waitSecondsLeft.value <= 0) {
      clearWaitTimer()
      downloadUrl.value = url
      
      window.open(url, '_blank', 'noopener,noreferrer')
    }
  }, 1000)
}

function getErrorMessage(error) {
  return error.message?.startsWith('Ошибка API')
    ? error.message
    : 'Не удалось подключиться к API'
}

async function submitForm() {
  apiError.value = ''
  downloadUrl.value = ''
  clearWaitTimer()
  isLoading.value = true

  try {
    const response = await fetch(getExcelExportApiUrl())

    if (!response.ok) {
      throw new Error(`Ошибка API: ${response.status}`)
    }

    const data = await parseApiResponse(response)
    const attachmentId = getAttachmentId(data)

    if (!attachmentId) {
      throw new Error('API не вернул id файла')
    }

    waitBeforeShowingDownload(buildAttachmentUrl(attachmentId))
  } catch (error) {
    apiError.value = getErrorMessage(error)
  } finally {
    isLoading.value = false
  }
}

// Реализация через классический AJAX
// function submitFormAjax() {
//   apiError.value = ''
//   downloadUrl.value = ''
//   clearWaitTimer()
//   isLoading.value = true
//
//   const request = new XMLHttpRequest()
//   request.open('GET', getExcelExportApiUrl())
//
//   request.onload = () => {
//     isLoading.value = false
//
//     if (request.status >= 200 && request.status < 300) {
//       let data = request.responseText
//
//       try {
//         data = JSON.parse(request.responseText)
//       } catch {}
//
//       const attachmentId = getAttachmentId(data)
//
//       if (!attachmentId) {
//         apiError.value = 'API не вернул id файла'
//         return
//       }
//
//       waitBeforeShowingDownload(buildAttachmentUrl(attachmentId))
//       return
//     }
//
//     apiError.value = `Ошибка API: ${request.status}`
//   }
//
//   request.onerror = () => {
//     isLoading.value = false
//     apiError.value = 'Не удалось выгрузить файл'
//   }
//
//   request.send()
// }

onBeforeUnmount(clearWaitTimer)
</script>
