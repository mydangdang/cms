/// <reference types="vite/client" />

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
}

interface ImportMetaEnv {
  readonly VITE_API_BASE_URL: string
  readonly VITE_APP_TITLE: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

// Vitest 全局变量
declare const describe: any
declare const it: any
declare const expect: any
declare const vi: any
declare const beforeEach: any
declare const beforeAll: any
declare const afterEach: any
declare const afterAll: any
