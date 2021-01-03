const readstream = require('./lib/readstream')
const mysql = require('mysql2/promise')

require('dotenv').config()

const main = async () => {
  /**
   * stdin から MySQL 読み込み設定を読み込む
   */
  const payload = JSON.parse(await readstream.stringify(process.stdin))
  if (payload === null) {
    return false
  }

  let client
  try {
    client = await mysql.createConnection({
      host: process.env.MYSQL_HOST,
      port: process.env.MYSQL_PORT,
      user: process.env.MYSQL_USER,
      password: process.env.MYSQL_PASSWORD,
      database: payload.database,
    })
    const [rows, fields] = await client.query(payload.query)
    console.log(JSON.stringify(rows))
    client.close()
  } catch (err) {
    console.error(err)
    client.close()
  }
}

main()