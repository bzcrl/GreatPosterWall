import { compact } from 'lodash'

export function GB(size) {
  return size * 1024 * 1024 * 1024
}

export const DISK_SIZE = {
  BD25: GB(23.28),
  BD50: GB(46.57),
  BD66: GB(61.47),
  BD100: GB(93.13),
}

export function splitIntoSections(text) {
  return compact(
    text
      .trim()
      .replace(/\n\r/, '\n')
      .split(/\n\s*\n/)
      .map((v) => v.trim())
  )
}

export function splitIntoLines(text) {
  return compact(
    text
      .trim()
      .replace(/\n\r/, '\n')
      .split(/\n/)
      .map((v) => v.trim())
  )
}

export function extractBBCode(bbcode) {
  const found = bbcode.match(/\[mediainfo\]([\s\S]*?)\[\/mediainfo\]/i)
  if (!found) {
    return
  }
  return found[1].trim()
}

export function removeMediainfoTag(bbcode) {
  return bbcode.replace('[mediainfo]', '').replace('[/mediainfo]', '').trim()
}

export function calcDiskType(size) {
  if (size <= DISK_SIZE.BD25) {
    return 'BD25'
  } else if (size <= DISK_SIZE.BD50) {
    return 'BD50'
  } else if (size <= DISK_SIZE.BD66) {
    return 'BD66'
  } else if (size <= DISK_SIZE.BD100) {
    return 'BD100'
  }
}
