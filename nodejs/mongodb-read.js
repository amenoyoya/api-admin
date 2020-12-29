const readstream = require('./lib/readstream')
const { MongoClient } = require('mongodb')
const assert = require('assert')
const omit = require('./lib/omit')

require('dotenv').config()

// 接続先URL
const url = (process.env.MONGO_USER && process.env.MONGO_PASSWORD)?
  `mongodb://${process.env.MONGO_USER}:${process.env.MONGO_PASSWORD}@${process.env.MONGO_HOST}:${process.env.MONGO_PORT}`:
  `mongodb://${process.env.MONGO_HOST}:${process.env.MONGO_PORT}`

/**
 * 追加オプション
 * MongoClient用オプション設定
 */
const connectOption = {
  useNewUrlParser: true,
  useUnifiedTopology: true,
}

/** 
 * cursor 共通メソッド: sort, limit, skip
 * @param {MongoDB.Cursor} collection
 * @return {MongoDB.Cursor}
 */
const sort_limit_skip = (cursor, condition) => {
  if (typeof condition === 'object' && typeof condition['$sort'] === 'object') {
    cursor = cursor.sort(condition['$sort'])
  }
  if (typeof condition === 'object' && typeof condition['$limit'] === 'number') {
    cursor = cursor.limit(condition['$limit'])
  }
  if (typeof condition === 'object' && typeof condition['$skip'] === 'number') {
    cursor = cursor.skip(condition['$skip'])
  }
  return cursor
}

/**
 * findメソッド
 * @param {MongoDB.Collection} collection
 * @param {[search_key]: object, $sort: object, $limit: number, $skip: number} condition
 *    @see https://github.com/louischatriot/nedb#finding-documents
 * @return {object[]} docs
 */
const find = async (collection, condition) => {
  // $sort, $limit, $skip キーを除く検索条件
  const query = omit(condition, ['$sort', '$limit', '$skip'])
  return await sort_limit_skip(collection.find(query), condition).toArray()
}

const main = async () => {
  /**
   * stdin から MongoDB 読み込み設定を読み込む
   */
  const config = JSON.parse(await readstream.stringify(process.stdin))
  if (config === null) {
    return false
  }

  /**
   * データベース接続
   * データベース接続用の引数追加
   */
  MongoClient.connect(url, connectOption, async (err, client) => {
    if (err !== null) {
      return false
    }
    try {
      const db = client.db(config.database)
      const collection = db.collection(config.collection)
      
      console.log(JSON.stringify(await find(collection, config.condition)))
      client.close()
    } catch (err) {
      console.log(err)
      client.close()
    }
  })
}

main()