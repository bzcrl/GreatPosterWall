/*
.hidden
  .bbcode-preview-text.group<id>
.hidden
  .bbcode-preview-html.group<id>
.bbcode-preview-button data-id=<id>
*/

const EDIT = translation.get('bbcode.edit')
const PREVIEW = translation.get('bbcode.preview')
const LOADING = translation.get('bbcode.loading')

export default class BBCodePreview {
  static register() {
    for (const buttonEl of document.querySelectorAll(
      '.bbcode-preview-button'
    )) {
      new BBCodePreview({ buttonEl }).register()
    }
  }

  // pairs: [ { textEl, htmlEl } ]
  constructor({ buttonEl }) {
    this.buttonEl = buttonEl
    this.id = buttonEl.getAttribute('data-id')
    this.cache = new Map()
  }

  register() {
    this.buttonEl.classList.remove('hidden')
    this.buttonEl.addEventListener('click', this.onClick.bind(this))
  }

  onClick() {
    // put it here to support add element
    const pairs = this.getPairs()

    const isPreview = this.buttonEl.value === PREVIEW
    this.buttonEl.value = isPreview ? EDIT : PREVIEW

    for (const { textEl, htmlEl } of pairs) {
      textEl.parentElement.classList.toggle('hidden')
      htmlEl.parentElement.classList.toggle('hidden')

      if (isPreview) {
        this.fetchAndRenderHtml({ textEl, htmlEl })
      }
    }
  }

  getPairs() {
    const textEls = [
      ...document.querySelectorAll(`.bbcode-preview-text.group${this.id}`),
    ]
    const htmlEls = [
      ...document.querySelectorAll(`.bbcode-preview-html.group${this.id}`),
    ]
    const pairs = textEls.map((textEl, i) => ({
      textEl,
      htmlEl: htmlEls[i],
    }))
    return pairs
  }

  async fetchAndRenderHtml({ textEl, htmlEl }) {
    if (this.cache.get(textEl) === textEl.value) {
      return
    }
    htmlEl.innerHTML = LOADING
    const html = await this.fetchHtml({ body: this.getBody(textEl) })
    htmlEl.innerHTML = html
    this.cache.set(textEl, textEl.value)
  }

  getBody(textEl) {
    const value = textEl.value
    if (textEl.getAttribute('data-type') === 'mediainfo') {
      if (!value.match(/\[mediainfo\]/)) {
        return `[mediainfo]\n${value}\n[/mediainfo]`
      }
    }
    return value
  }

  async fetchHtml({ body }) {
    const formData = new FormData()
    formData.set('body', body)
    const res = await fetch('ajax.php?action=preview', {
      method: 'POST',
      body: formData,
    })
    const html = await res.text()
    return html
  }
}
