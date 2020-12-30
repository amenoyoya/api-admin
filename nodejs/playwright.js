const readstream = require('./lib/readstream')
const { webkit } = require('playwright')
const jsdom = require('jsdom')

/**
 * ページ遷移
 * @param {Page} page
 * @param {string} url
 * @return {*}
 */
const goto = async (page, url) => {
  try {
    await page.goto(url)
    return {result: true}
  } catch (err) {
    return {
      result: false,
      error: err.toString(),
    }
  }
}

/**
 * HTML要素スクレイピング
 * @param {Page} page 
 * @param {object} condition {
 *    selector: xpath or cssSelector,
 *    attributes: ['innerText'|'innerHTML'|'outerHTML'|'href'|...],
 *    actions: [{action: 'click'|'dblclick'|'check'|'fill'|..., args: [...]}, ...]
 * }
 * @return {object[]} 
 */
const scrape = async (page, condition) => {
  const result = []
  for (const el of await page.$$(condition.selector)) {
    // HTML要素へのアクション実行
    const action_result = {}
    if (Array.isArray(condition.actions)) {
      for (const action of condition.actions) {
        try {
          if (typeof el[action.action] === 'function') {
            action.args? await el[action.action](...action.args): await el[action.action]()
            action_result[action.action] = {result: true}
          } else {
            action_result[action.action] = {result: false}
          }
        } catch (err) {
          action_result[action.action] = {
            result: false,
            error: err.toString(),
          }
        }
      }
    }
    // HTML要素属性の取得
    const attr_result = await el.evaluate((el, attributes) => {
      const result = {}
      for (const attr of attributes) {
        result[attr] = el[attr]
      }
      return result
    }, condition.attributes)
    // 結果の push
    result.push({
      '$actions': Object.keys(action_result).length > 0? action_result: undefined,
      ...attr_result,
    })
  }
  return result
}

/**
 * スクリーンショット撮影
 * @param {Page} page 
 * @param {string} filename
 * @return {*} 
 */
const screenshot = async (page, filename) => {
  try {
    await page.screenshot({path: filename})
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
 * Playwright 実行メイン
 * @param {Page} page
 * @param {object} scenario
 * @return {*}
 */
const execute = async (page, scenario) => {
  const result = {}
  // ページ読み込み
  if (scenario.goto) {
    result.goto = await goto(page, scenario.goto)
  }
  // スクレイピング
  if (scenario.scrape) {
    if (Array.isArray(scenario.scrape)) {
      result.scrape = []
      for (const condition of scenario.scrape) {
        result.scrape.push(await scrape(page, condition))
      }
    } else {
      result.scrape = await scrape(page, scenario.scrape)
    }
  }
  // screenshot撮影
  if (scenario.screenshot) {
    result.screenshot = await screenshot(page, scenario.screenshot)
  }
  return result
}

let browser

(async () => {
  browser = await webkit.launch() // or 'firefox','chromium'

  const context = await browser.newContext({locale: 'ja'})
  const page = await context.newPage()
  
  // stdin から payload 読み込み
  const payload = JSON.parse(await readstream.stringify(process.stdin))
  if (payload === null) {
    return false
  }
  // Playwright 実行
  if (Array.isArray(payload)) {
    const result = []
    for (const scenario of payload) {
      result.push(await execute(page, scenario))
    }
    console.log(JSON.stringify(result))
  } else {
    console.log(JSON.stringify(await execute(page, payload)))
  }
  await browser.close()
})().catch(error => {
  // console.log(JSON.stringify({error: error.toString()}))
  console.log(error)
  browser.close()
});