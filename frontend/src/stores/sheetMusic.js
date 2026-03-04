import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSheetMusicStore = defineStore('sheetMusic', () => {
  const sheets = ref([
    {
      id: 1,
      title: 'Piano Sonata No. 14 "Moonlight"',
      composer: 'Ludwig van Beethoven',
      instrument: 'Piano',
      difficulty: 'Advanced',
      price: 12.99,
      description:
        "Complete sonata in three movements. One of Beethoven's most famous piano pieces.",
      coverImage: 'https://i.scdn.co/image/ab67616d0000b2733fa0a17a5ba2192f04048b72',
      pages: 24,
      format: 'PDF',
      rating: 4.8,
      reviews: 156,
    },
    {
      id: 2,
      title: 'Violin Concerto in D Major',
      composer: 'Pyotr Ilyich Tchaikovsky',
      instrument: 'Violin',
      difficulty: 'Advanced',
      price: 24.99,
      description: 'One of the most challenging and beloved violin concertos in the repertoire.',
      coverImage: 'https://i.scdn.co/image/ab67616d0000b27307801cb34f977ca9bab2baa4',
      pages: 45,
      format: 'PDF',
      rating: 4.9,
      reviews: 89,
    },
    {
      id: 3,
      title: 'Guitar Etudes for Beginners',
      composer: 'Fernando Sor',
      instrument: 'Guitar',
      difficulty: 'Beginner',
      price: 9.99,
      description: '20 progressive etudes perfect for classical guitar students.',
      coverImage: 'https://i.scdn.co/image/ab67616d0000b2733d74a93005e7db8a8a277a5b',
      pages: 32,
      format: 'PDF',
      rating: 4.5,
      reviews: 234,
    },
    {
      id: 4,
      title: 'Jazz Standards Collection',
      composer: 'Various',
      instrument: 'Piano',
      difficulty: 'Intermediate',
      price: 18.99,
      description:
        '20 jazz classics arranged for solo piano including "Autumn Leaves", "Misty", and more.',
      coverImage:
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSFTlEltQn6R2lV4G951PqgO1g9g10j_6L41g&s',
      pages: 60,
      format: 'PDF + Audio',
      rating: 4.7,
      reviews: 312,
    },
    {
      id: 5,
      title: 'Cello Suite No. 1',
      composer: 'Johann Sebastian Bach',
      instrument: 'Cello',
      difficulty: 'Intermediate',
      price: 14.99,
      description: 'Complete suite including the famous Prelude. Urtext edition.',
      coverImage: 'https://i.scdn.co/image/ab67616d0000b273261feb89ee859b598bd34a97',
      pages: 18,
      format: 'PDF',
      rating: 4.9,
      reviews: 178,
    },
    {
      id: 6,
      title: 'Flute Sonata in E-flat Major',
      composer: 'Johann Sebastian Bach',
      instrument: 'Flute',
      difficulty: 'Advanced',
      price: 16.99,
      description: 'Beautiful sonata for flute and continuo. Includes figured bass realization.',
      coverImage:
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQp1hMmUn1KHZ91xs4HqpuhOJWsC_hbYAN0aA&s',
      pages: 28,
      format: 'PDF',
      rating: 4.6,
      reviews: 67,
    },
  ])

  function addSheet(payload) {
    const nextId =
      sheets.value.length > 0 ? Math.max(...sheets.value.map((sheet) => sheet.id || 0)) + 1 : 1

    const newSheet = {
      id: nextId,
      title: payload.title,
      composer: payload.composer,
      instrument: payload.instrument,
      difficulty: payload.difficulty,
      price: Number(payload.price),
      description: payload.description,
      coverImage: payload.coverImage,
      pages: Number(payload.pages),
      format: payload.format,
      rating: 0,
      reviews: 0,
    }

    sheets.value.unshift(newSheet)
  }

  return { sheets, addSheet }
})
