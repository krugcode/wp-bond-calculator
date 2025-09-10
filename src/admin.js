import './app.css'
import { mount } from 'svelte'
import Admin from './Admin.svelte'

const app = mount(Admin, {
  target: document.getElementById('bc-admin-app'),
  props: {}
})

export default app
