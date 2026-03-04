-- First, make sure you have instruments and categories
INSERT INTO instruments (name, slug, icon, description) VALUES
('Piano', 'piano', 'bi-piano', 'Keyboard instruments including piano, organ, and harpsichord'),
('Violin', 'violin', 'bi-music-note', 'String instruments played with a bow'),
('Guitar', 'guitar', 'bi-guitar', 'String instruments including classical, acoustic, and electric guitar'),
('Cello', 'cello', 'bi-music-note', 'Large string instrument with rich, deep tone'),
('Flute', 'flute', 'bi-music-note', 'Woodwind instrument with bright, clear sound'),
('Voice', 'voice', 'bi-mic', 'Vocal music for soprano, alto, tenor, and bass'),
('Orchestra', 'orchestra', 'bi-music-note-beamed', 'Full orchestral arrangements'),
('Chamber', 'chamber', 'bi-people', 'Small ensemble music'),
('Harp', 'harp', 'bi-music-note', 'String instrument played by plucking'),
('Clarinet', 'clarinet', 'bi-music-note', 'Woodwind instrument with versatile tone');

INSERT INTO categories (name, slug, icon, description) VALUES
('Classical', 'classical', 'bi-music-note', 'Classical music from Baroque to Contemporary'),
('Romantic', 'romantic', 'bi-heart', 'Romantic era music full of emotion and expression'),
('Baroque', 'baroque', 'bi-building', 'Ornate music from the 17th and 18th centuries'),
('Jazz', 'jazz', 'bi-music-note', 'Jazz standards, improvisation, and arrangements'),
('Blues', 'blues', 'bi-music-note', 'Blues music with soulful expression'),
('Pop', 'pop', 'bi-music-note', 'Contemporary popular music arrangements'),
('Rock', 'rock', 'bi-music-note', 'Rock and alternative music'),
('Folk', 'folk', 'bi-tree', 'Traditional and folk music from around the world'),
('Educational', 'educational', 'bi-book', 'Teaching materials, etudes, and method books'),
('Sacred', 'sacred', 'bi-church', 'Religious, gospel, and spiritual music'),
('Film', 'film', 'bi-film', 'Movie soundtracks and film scores'),
('Christmas', 'christmas', 'bi-gift', 'Holiday and Christmas music'),
('Wedding', 'wedding', 'bi-heart', 'Wedding ceremony and reception music'),
('Contemporary', 'contemporary', 'bi-music-note', 'Modern and contemporary compositions'),
('Solo', 'solo', 'bi-person', 'Music for solo performers'),
('Duet', 'duet', 'bi-people', 'Music for two performers');

-- Now insert sample sheet music
INSERT INTO sheet_music (
    title, composer, description, instrument_id, category_id, 
    difficulty, price, pages, format, file_path, cover_image, 
    is_featured, is_premium, rating, reviews_count, downloads_count, views_count
) VALUES
-- PIANO SHEET MUSIC (instrument_id = 1)
(
    'Moonlight Sonata (Complete)',
    'Ludwig van Beethoven',
    'One of Beethoven\'s most famous piano pieces, the "Moonlight" Sonata (Piano Sonata No. 14 in C-sharp minor) is a masterpiece of the Romantic era. This complete edition includes all three movements: Adagio sostenuto, Allegretto, and Presto agitato. The first movement is one of the most recognizable pieces in classical music.',
    1, 2, 'Advanced', 14.99, 24, 'PDF', '/uploads/sheets/beethoven-moonlight.pdf', '/uploads/covers/beethoven-moonlight.jpg', 1, 1, 4.9, 1245, 5678, 15678
),
(
    'Für Elise',
    'Ludwig van Beethoven',
    'Bagatelle No. 25 in A minor, better known as "Für Elise," is one of Beethoven\'s most popular compositions. This urtext edition includes fingerings and performance notes. Perfect for intermediate pianists looking to add a classic to their repertoire.',
    1, 1, 'Intermediate', 6.99, 8, 'PDF', '/uploads/sheets/beethoven-fur-elise.pdf', '/uploads/covers/beethoven-fur-elise.jpg', 1, 0, 4.8, 2341, 8976, 23456
),
(
    'Nocturne in E-flat Major, Op. 9 No. 2',
    'Frédéric Chopin',
    'Chopin\'s most famous nocturne, featuring beautiful bel canto style melody over broken chord accompaniment. This edition includes detailed pedaling and fingering suggestions from renowned Chopin interpreter. A must-have for any serious pianist.',
    1, 2, 'Advanced', 8.99, 12, 'PDF', '/uploads/sheets/chopin-nocturne.pdf', '/uploads/covers/chopin-nocturne.jpg', 1, 1, 4.9, 1876, 6543, 18765
),
(
    'Clair de Lune',
    'Claude Debussy',
    'The third movement of Debussy\'s Suite Bergamasque, "Clair de Lune" is one of the most beautiful and evocative pieces in the piano repertoire. This carefully edited edition includes performance suggestions and historical notes about this Impressionist masterpiece.',
    1, 14, 'Intermediate', 7.99, 10, 'PDF', '/uploads/sheets/debussy-clair-de-lune.pdf', '/uploads/covers/debussy-clair-de-lune.jpg', 1, 0, 4.8, 1567, 5432, 14567
),
(
    'Gymnopédie No. 1',
    'Erik Satie',
    'Satie\'s most famous composition, with its hauntingly beautiful melody and unconventional harmony. This edition includes the original French instructions and performance notes. Perfect for intermediate pianists exploring Impressionist music.',
    1, 14, 'Intermediate', 5.99, 6, 'PDF', '/uploads/sheets/satie-gymnopedie.pdf', '/uploads/covers/satie-gymnopedie.jpg', 0, 0, 4.7, 876, 3456, 9876
),
(
    'Piano Adventures - Level 1',
    'Nancy & Randall Faber',
    'The basic method book for beginning pianists. Includes: Unit 1: Basic Piano Keys, Unit 2: Note Values, Unit 3: Intervals, Unit 4: C Position, Unit 5: G Position. 50+ progressive pieces with teacher duets.',
    1, 9, 'Beginner', 12.99, 72, 'PDF', '/uploads/sheets/faber-level1.pdf', '/uploads/covers/faber-level1.jpg', 1, 0, 4.9, 3421, 12456, 27890
),
(
    'The Jazz Piano Book',
    'Mark Levine',
    'The most comprehensive jazz piano method ever published. Covers voicings, improvisation, theory, and over 300 musical examples. Includes transcriptions of solos by Bill Evans, Herbie Hancock, and more.',
    1, 4, 'Advanced', 34.99, 320, 'PDF', '/uploads/sheets/levine-jazz-piano.pdf', '/uploads/covers/levine-jazz-piano.jpg', 1, 1, 4.9, 2345, 8765, 19876
),
(
    'Piano Man',
    'Billy Joel',
    'Official sheet music for Billy Joel\'s signature song. Complete piano/vocal arrangement with chord symbols and lyrics. Includes historical notes about the song\'s composition and its place in pop culture.',
    1, 6, 'Intermediate', 5.99, 8, 'PDF', '/uploads/sheets/joel-piano-man.pdf', '/uploads/covers/joel-piano-man.jpg', 0, 0, 4.8, 654, 2345, 7654
),
(
    'River Flows in You',
    'Yiruma',
    'Yiruma\'s most beloved composition, featured in the film "Twilight." This beautiful, flowing piece has become a modern piano classic. Includes fingering suggestions and pedaling marks.',
    1, 14, 'Intermediate', 4.99, 6, 'PDF', '/uploads/sheets/yiruma-river-flows.pdf', '/uploads/covers/yiruma-river-flows.jpg', 1, 0, 4.8, 2341, 7654, 18765
),
(
    '12 Bar Blues for Piano',
    'Various',
    'A comprehensive collection of 12-bar blues patterns, riffs, and complete pieces. Includes left-hand patterns, right-hand licks, walking bass lines, and turnarounds. Perfect for pianists wanting to learn blues style.',
    1, 5, 'Intermediate', 11.99, 48, 'PDF + Audio', '/uploads/sheets/blues-piano.pdf', '/uploads/covers/blues-piano.jpg', 0, 0, 4.6, 432, 1234, 5432
),

-- VIOLIN SHEET MUSIC (instrument_id = 2)
(
    'Violin Concerto in D Major, Op. 35',
    'Pyotr Ilyich Tchaikovsky',
    'One of the most beloved violin concertos in the repertoire. This edition includes the complete orchestral score with solo violin part. Includes cadenzas by著名 violinist and historical performance notes.',
    2, 2, 'Advanced', 29.99, 85, 'PDF', '/uploads/sheets/tchaikovsky-violin-concerto.pdf', '/uploads/covers/tchaikovsky-violin.jpg', 1, 1, 4.9, 876, 3456, 10987
),
(
    'Violin Sonata No. 5 "Spring"',
    'Ludwig van Beethoven',
    'Beethoven\'s "Spring" Sonata for violin and piano is a joyful masterpiece of the chamber repertoire. Complete score and parts with fingering and bowing suggestions. Perfect for advanced students and professionals.',
    2, 1, 'Advanced', 16.99, 42, 'PDF', '/uploads/sheets/beethoven-spring.pdf', '/uploads/covers/beethoven-spring.jpg', 1, 1, 4.8, 543, 2345, 8765
),
(
    'Meditation from Thaïs',
    'Jules Massenet',
    'This exquisite piece for violin and piano is one of the most beautiful in the repertoire. Includes piano accompaniment and solo violin part with bowings and fingerings. Perfect for recitals and weddings.',
    2, 2, 'Intermediate', 7.99, 12, 'PDF', '/uploads/sheets/massenet-meditation.pdf', '/uploads/covers/massenet-meditation.jpg', 1, 0, 4.8, 987, 4321, 12345
),
(
    'Suzuki Violin School, Vol. 1',
    'Shinichi Suzuki',
    'The world-famous Suzuki method for beginning violinists. Includes: Twinkle Variations, Lightly Row, Song of the Wind, Go Tell Aunt Rhody, and many more. Comes with CD and piano accompaniment parts.',
    2, 9, 'Beginner', 19.99, 48, 'PDF + Audio', '/uploads/sheets/suzuki-violin-1.pdf', '/uploads/covers/suzuki-violin.jpg', 1, 0, 4.9, 4321, 15678, 34567
),
(
    'The Four Seasons - Summer',
    'Antonio Vivaldi',
    'Vivaldi\'s dramatic "Summer" concerto from The Four Seasons. Complete solo violin part with piano reduction. Includes detailed bowing suggestions and performance notes for the challenging storm movement.',
    2, 3, 'Advanced', 12.99, 24, 'PDF', '/uploads/sheets/vivaldi-summer.pdf', '/uploads/covers/vivaldi-summer.jpg', 0, 1, 4.8, 654, 2987, 9876
),

-- GUITAR SHEET MUSIC (instrument_id = 3)
(
    'Recuerdos de la Alhambra',
    'Francisco Tárrega',
    'Tárrega\'s masterpiece featuring the challenging tremolo technique. This classical guitar piece evokes the beauty of the Alhambra palace in Spain. Includes fingering and technique notes for mastering the tremolo.',
    3, 2, 'Advanced', 8.99, 8, 'PDF', '/uploads/sheets/tarrega-recuerdos.pdf', '/uploads/covers/tarrega-recuerdos.jpg', 1, 1, 4.9, 765, 3456, 10987
),
(
    'Romanza (Spanish Romance)',
    'Anonymous',
    'One of the most famous pieces for classical guitar. This anonymous romance from 19th century Spain is a must-know for every guitarist. Includes multiple variations and performance notes.',
    3, 1, 'Intermediate', 5.99, 6, 'PDF', '/uploads/sheets/romanza.pdf', '/uploads/covers/romanza.jpg', 1, 0, 4.8, 876, 4321, 12345
),
(
    'Asturias (Leyenda)',
    'Isaac Albéniz',
    'Originally written for piano but now famous as a guitar piece, Asturias showcases the flamenco influences in Albéniz\'s music. This authentic transcription captures the guitaristic essence of the piece.',
    3, 2, 'Advanced', 9.99, 10, 'PDF', '/uploads/sheets/albeniz-asturias.pdf', '/uploads/covers/albeniz-asturias.jpg', 1, 1, 4.8, 543, 2345, 8765
),
(
    'The Beatles Complete',
    'The Beatles',
    'Complete guitar arrangements for 200 Beatles songs. Includes accurate chords, tabs, and lyrics. Songs include: Yesterday, Hey Jude, Let It Be, Blackbird, Here Comes the Sun, and many more.',
    3, 6, 'Intermediate', 39.99, 400, 'PDF', '/uploads/sheets/beatles-complete.pdf', '/uploads/covers/beatles.jpg', 1, 1, 4.9, 2345, 8765, 23456
),
(
    'Classical Guitar Method, Vol. 1',
    'Aaron Shearer',
    'The definitive classical guitar method. Covers proper technique, reading music, and 50 progressive pieces. Includes exercises for right-hand fingering, left-hand position, and musical expression.',
    3, 9, 'Beginner', 24.99, 120, 'PDF', '/uploads/sheets/shearer-method.pdf', '/uploads/covers/shearer-method.jpg', 1, 0, 4.8, 654, 3456, 9876
),

-- CELLO SHEET MUSIC (instrument_id = 4)
(
    'Cello Suite No. 1 in G Major',
    'Johann Sebastian Bach',
    'Bach\'s complete Suite No. 1 for unaccompanied cello, including the famous Prelude. This urtext edition includes historical notes and suggested bowings. Essential for every cellist.',
    4, 3, 'Advanced', 14.99, 20, 'PDF', '/uploads/sheets/bach-cello-suite1.pdf', '/uploads/covers/bach-cello-suite.jpg', 1, 1, 4.9, 1432, 5678, 15678
),
(
    'The Swan (Le Cygne)',
    'Camille Saint-Saëns',
    'The beautiful cello solo from "The Carnival of the Animals." This edition includes piano accompaniment and cello part with phrasing and bowing suggestions. Perfect for recitals.',
    4, 2, 'Intermediate', 6.99, 8, 'PDF', '/uploads/sheets/saint-saens-swan.pdf', '/uploads/covers/saint-saens-swan.jpg', 1, 0, 4.8, 876, 3456, 10987
),
(
    'Cello Concerto in E minor',
    'Edward Elgar',
    'One of the greatest cello concertos ever written. Complete solo cello part with piano reduction. Includes orchestral cues and performance notes from著名 cellist.',
    4, 2, 'Advanced', 22.99, 56, 'PDF', '/uploads/sheets/elgar-cello-concerto.pdf', '/uploads/covers/elgar-cello.jpg', 0, 1, 4.9, 432, 1987, 7654
),

-- FLUTE SHEET MUSIC (instrument_id = 5)
(
    'Syrinx',
    'Claude Debussy',
    'Debussy\'s masterpiece for solo flute, written for the play "Psyché." This revolutionary piece uses the full range of the flute and requires advanced breath control. Includes performance notes.',
    5, 14, 'Advanced', 5.99, 4, 'PDF', '/uploads/sheets/debussy-syrinx.pdf', '/uploads/covers/debussy-syrinx.jpg', 1, 0, 4.7, 543, 2345, 8765
),
(
    'Flute Sonata in D Major',
    'Georg Philipp Telemann',
    'One of Telemann\'s most popular flute sonatas. Complete score with figured bass realization. Includes ornamentation suggestions and historical performance notes.',
    5, 3, 'Intermediate', 10.99, 24, 'PDF', '/uploads/sheets/telemann-flute-sonata.pdf', '/uploads/covers/telemann-flute.jpg', 0, 0, 4.6, 321, 1234, 5432
),
(
    'The Magic Flute - Opera Excerpts',
    'Wolfgang Amadeus Mozart',
    'All the famous arias and ensembles from Mozart\'s beloved opera, arranged for flute and piano. Includes: Queen of the Night, Papageno\'s songs, and more.',
    5, 1, 'Advanced', 18.99, 64, 'PDF', '/uploads/sheets/mozart-magic-flute.pdf', '/uploads/covers/mozart-magic-flute.jpg', 0, 1, 4.8, 234, 987, 4321
),

-- VOCAL SHEET MUSIC (instrument_id = 6)
(
    'Ave Maria',
    'Franz Schubert',
    'Schubert\'s beloved setting of the Ave Maria. For high voice with piano accompaniment. Includes German and Latin texts with IPA pronunciation guide. Perfect for weddings and concerts.',
    6, 10, 'Intermediate', 7.99, 8, 'PDF', '/uploads/sheets/schubert-ave-maria.pdf', '/uploads/covers/schubert-ave-maria.jpg', 1, 0, 4.9, 1432, 5678, 16789
),
(
    'The Art of Singing - 24 Vocalises',
    'Mathilde Marchesi',
    'Essential vocal exercises for developing technique. Covers breath control, agility, range extension, and expression. Includes exercises for soprano, mezzo, tenor, and bass voices.',
    6, 9, 'Intermediate', 19.99, 96, 'PDF', '/uploads/sheets/marchesi-vocalises.pdf', '/uploads/covers/marchesi-vocalises.jpg', 1, 1, 4.8, 654, 2987, 9876
),
(
    'Nessun Dorma',
    'Giacomo Puccini',
    'The famous tenor aria from "Turandot." Complete vocal score with piano reduction and original Italian text. Includes translation and historical notes about this iconic aria.',
    6, 1, 'Advanced', 6.99, 10, 'PDF', '/uploads/sheets/puccini-nessun-dorma.pdf', '/uploads/covers/puccini-nessun-dorma.jpg', 1, 1, 4.9, 876, 3456, 12345
),

-- ORCHESTRA (instrument_id = 7)
(
    'Symphony No. 5 in C minor',
    'Ludwig van Beethoven',
    'Complete orchestral score of Beethoven\'s iconic Symphony No. 5. Includes all movements with historical notes and analysis. Perfect for study and conducting.',
    7, 1, 'Professional', 49.99, 280, 'PDF', '/uploads/sheets/beethoven-symphony5.pdf', '/uploads/covers/beethoven-symphony5.jpg', 1, 1, 4.9, 765, 3456, 10987
),
(
    'The Planets, Op. 32',
    'Gustav Holst',
    'Complete orchestral score of Holst\'s masterpiece. All seven movements: Mars, Venus, Mercury, Jupiter, Saturn, Uranus, Neptune. Includes program notes and orchestration analysis.',
    7, 2, 'Professional', 59.99, 350, 'PDF', '/uploads/sheets/holst-planets.pdf', '/uploads/covers/holst-planets.jpg', 0, 1, 4.9, 543, 2345, 8765
),

-- CHRISTMAS MUSIC (category_id = 12)
(
    'A Christmas Carol Collection',
    'Various',
    '50 classic Christmas carols arranged for piano and voice. Includes: Silent Night, Joy to the World, O Holy Night, Hark! The Herald Angels Sing, and many more. Easy to intermediate arrangements.',
    1, 12, 'Intermediate', 16.99, 120, 'PDF', '/uploads/sheets/christmas-carols.pdf', '/uploads/covers/christmas-carols.jpg', 1, 0, 4.8, 2345, 8765, 19876
),
(
    'The Nutcracker Suite (Easy Piano)',
    'Pyotr Ilyich Tchaikovsky',
    'Simplified piano arrangements of the most beloved Nutcracker pieces. Includes: Dance of the Sugar Plum Fairy, Waltz of the Flowers, Russian Dance, and more. Perfect for holiday recitals.',
    1, 12, 'Intermediate', 12.99, 32, 'PDF', '/uploads/sheets/tchaikovsky-nutcracker.pdf', '/uploads/covers/tchaikovsky-nutcracker.jpg', 1, 0, 4.8, 1432, 5678, 14567
),

-- JAZZ STANDARDS (category_id = 4)
(
    'The Real Book - Volume 1',
    'Various',
    'The famous "Fake Book" containing 400 jazz standards. Melody lines, chord changes, and lyrics for every jazz musician\'s essential repertoire. Includes: Autumn Leaves, Girl from Ipanema, All of Me, and more.',
    1, 4, 'Intermediate', 49.99, 462, 'PDF', '/uploads/sheets/real-book-vol1.pdf', '/uploads/covers/real-book.jpg', 1, 1, 4.9, 3456, 12345, 34567
),
(
    'Jazz Standards for Solo Piano',
    'Various',
    '20 jazz classics arranged for solo piano. Includes: Misty, Take the A Train, My Funny Valentine, Round Midnight, and more. Each arrangement includes chord symbols and performance notes.',
    1, 4, 'Advanced', 19.99, 88, 'PDF', '/uploads/sheets/jazz-standards-piano.pdf', '/uploads/covers/jazz-standards-piano.jpg', 1, 1, 4.8, 876, 3456, 10987
),

-- FILM MUSIC (category_id = 11)
(
    'Harry Potter - Complete Collection',
    'John Williams',
    'Piano solos from all Harry Potter films. Includes: Hedwig\'s Theme, Harry\'s Wondrous World, Fawkes the Phoenix, and many more. Intermediate to advanced arrangements.',
    1, 11, 'Advanced', 24.99, 120, 'PDF', '/uploads/sheets/harry-potter.pdf', '/uploads/covers/harry-potter.jpg', 1, 1, 4.9, 2345, 9876, 23456
),
(
    'Star Wars - The Skywalker Saga',
    'John Williams',
    'Complete piano arrangements of the main themes from all nine Skywalker saga films. Includes: Main Title, The Imperial March, Princess Leia\'s Theme, Duel of the Fates, and more.',
    1, 11, 'Advanced', 29.99, 150, 'PDF', '/uploads/sheets/star-wars.pdf', '/uploads/covers/star-wars.jpg', 1, 1, 4.9, 1876, 7654, 19876
),

-- WEDDING MUSIC (category_id = 13)
(
    'The Ultimate Wedding Music Collection',
    'Various',
    '100 essential pieces for weddings. Includes: Bridal Chorus, Wedding March, Canon in D, Ave Maria, and popular songs. Arranged for piano, organ, and voice.',
    1, 13, 'Intermediate', 27.99, 200, 'PDF', '/uploads/sheets/wedding-collection.pdf', '/uploads/covers/wedding-collection.jpg', 1, 0, 4.8, 1432, 5432, 12345
),
(
    'Canon in D',
    'Johann Pachelbel',
    'The most requested piece for weddings. Multiple arrangements included: solo piano, piano and violin, string quartet, and organ. Intermediate to advanced difficulty.',
    1, 13, 'Intermediate', 7.99, 16, 'PDF', '/uploads/sheets/pachelbel-canon.pdf', '/uploads/covers/pachelbel-canon.jpg', 1, 0, 4.8, 2341, 8765, 19876
);

-- Update rating and reviews_count for consistency
UPDATE sheet_music SET 
    rating = ROUND(4.5 + RAND() * 0.4, 1),
    reviews_count = FLOOR(50 + RAND() * 950),
    downloads_count = FLOOR(100 + RAND() * 9000),
    views_count = FLOOR(1000 + RAND() * 30000);