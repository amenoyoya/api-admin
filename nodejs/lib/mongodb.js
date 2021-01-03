const { MongoClient } = require('mongodb')
const omit = require('./omit')

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

/**
 * データベース接続
 * @param {string} url mongodb://{username}:{password}@{host}:{port}
 * @return {MongoClient} client
 */
const connect = async url => {
  return await MongoClient.connect(url,  {
    useNewUrlParser: true,
    useUnifiedTopology: true,
  })
}

/**
 * データベースクエリ実行
 * @param {MongoClient} client
 * @param {*} query {database: string, collection: string, find: object, insert: object, update: object, delete: object}
 * @return {*}
 */
const execute = async (client, query) => {
  const db = client.db(query.database)
  const collection = db.collection(query.collection)
  const result = {}
  
  if (typeof query.find === 'object') {
    result.find = await find(collection, query.find)
  }
  if (typeof query.insert === 'object') {
    if (Array.isArray(query.insert)) {
      result.insert = await collection.insertMany(query.insert)
    } else {
      result.insert = await collection.insertOne(query.insert)
    }
  }
  if (Array.isArray(query.update)) {
    result.update = await collection.updateMany(...query.update)
  }
  if (typeof query.delete === 'object') {
    result.delete = await collection.deleteMany(query.delete)
  }
  return result
}

module.exports = {
  connect, execute
}