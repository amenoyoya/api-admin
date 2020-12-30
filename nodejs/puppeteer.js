const readstream = require('./lib/readstream')
const puppet = require('./lib/puppet')
const fs = require('fs')
const { cond } = require('lodash')

/**
 * element取得
 * @param {Puppeteer.Page} page
 * @param {array|object} conditions
 * @return {*}
 */
const getElements = async (page, conditions) => {
  if (Array.isArray(conditions)) {
    const result = []
    for (const condition of conditions) {
      result.push(await puppet.elements(page, condition.selector, condition.option || ['text', 'attributes']))
    }
    return result
  }
  return await puppet.elements(page, conditions.selector, conditions.option || ['text', 'attributes'])
}

/**
 * screenshot撮影
 * @param {Puppeteer.Page} page
 * @param {string} filename
 * @return {*}
 */
const takeScreenshot = async (page, filename) => {
  try {
    fs.writeFileSync(filename, await puppet.screenshot(page))
    return {
      result: true,
      filename: filename,
    }
  } catch (err) {
    return {
      result: false,
      error: err.toString(),
    }
  }
}

/**
 * テキストボックス入力
 * @param {Puppeteer.Page} page
 * @param {object} condition
 * @return {*}
 */
const inputText = async (page, condition) => {
  if (await puppet.input(page, condition.selector, condition.text)) {
    return {
      result: true
    }
  }
  return {
    result: false,
    error: puppet.error(),
  }
}

/**
 * セレクトボックスから選択
 * @param {Puppeteer.Page} page
 * @param {object} condition
 * @return {*}
 */
const inputSelect = async (page, condition) => {
  if (await puppet.select(page, condition.selector, condition.value)) {
    return {
      result: true
    }
  }
  return {
    result: false,
    error: puppet.error(),
  }
}

/**
 * クリック
 * @param {Puppeteer.Page} page
 * @param {string} selector
 * @return {*}
 */
const clickButton = async (page, selector) => {
  if (await puppet.click(page, selector)) {
    return {
      result: true
    }
  }
  return {
    result: false,
    error: puppet.error(),
  }
}

/**
 * Puppeteer 実行メイン
 * @param {Puppeteer.Page} page
 * @param {object} scenario
 * @return {*}
 */
const execute = async (page, scenario) => {
  const result = {}
  // ページ読み込み
  if (scenario.goto) {
    if(! await puppet.goto(page, scenario.goto)) {
      result.goto = {
        result: false,
        error: puppet.error()
      }
    } else {
      result.goto = {result: true}
    }
  }
  // element取得
  if (scenario.get) {
    result.get = await getElements(page, scenario.get)
  }
  // screenshot撮影
  if (scenario.screenshot) {
    result.screenshot = await takeScreenshot(page, scenario.screenshot)
  }
  // input box への入力
  if (scenario.input) {
    if (Array.isArray(scenario.input)) {
      result.input = []
      for (const condition of scenario.input) {
        result.input.push(await inputText(page, condition))
      }
    } else {
      result.input = await inputText(page, scenario.input)
    }
  }
  // select box の選択
  if (scenario.select) {
    if (Array.isArray(scenario.select)) {
      result.select = []
      for (const condition of scenario.select) {
        result.select.push(await selectText(page, condition))
      }
    } else {
      result.select = await selectText(page, scenario.select)
    }
  }
  // click 実行
  if (scenario.click) {
    if (Array.isArray(scenario.click)) {
      result.click = []
      for (const selector of scenario.click) {
        result.click.push(await clickButton(page, selector))
      }
    } else {
      result.click = await clickButton(page, scenario.click)
    }
  }
  return result
}

const main = async () => {
  // stdin から payload 読み込み
  const payload = JSON.parse(await readstream.stringify(process.stdin))
  if (payload === null) {
    return false
  }
  // puppeteer 起動
  if (! await puppet.launch()) {
    console.log(JSON.stringify({error: puppet.error()}))
    await puppet.terminate()
    return false
  }
  const page = await puppet.page()
  // scenario 実行
  if (Array.isArray(payload)) {
    const result = []
    for (const scenario of payload) {
      result.push(await execute(page, scenario))
    }
    console.log(JSON.stringify(result))
  } else {
    console.log(JSON.stringify(await execute(page, payload)))
  }
  await puppet.terminate()
}

main()