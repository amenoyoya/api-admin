const puppet = require('./lib/puppet')
const fs = require('fs')

const main = async () => {
  if (! await puppet.launch()) {
    console.log(puppet.error())
    return false
  }
  const page = await puppet.page()
  await puppet.goto(page, 'https://www.google.co.jp')
  fs.writeFileSync('./google.png', await puppet.screenshot(page))
  await puppet.terminate()
}

main()