export function formatPriceIDR(price) {
  const numPrice = Number(price)

  if (numPrice >= 1000000000) {
    return `Rp${(numPrice / 1000000000).toFixed(1)}B`
  } else if (numPrice >= 1000000) {
    return `Rp${(numPrice / 1000000).toFixed(1)}M`
  } else if (numPrice >= 1000) {
    return `Rp${(numPrice / 1000).toFixed(1)}K`
  } else {
    return `Rp${numPrice.toFixed(2)}`
  }
}
