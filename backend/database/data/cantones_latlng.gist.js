const cantones = [
  {
    name: "San Lorenzo",
    type: "city",
    latlng: [0.98506345, -78.6869332946931]
  },
  {
    name: "Eloy Alfaro",
    type: "city",
    latlng: [0.874044, -78.9950332281882]
  },
  {
    name: "Rioverde",
    type: "city",
    latlng: [1.0247745, -79.4326189854836]
  },
  { name: "Esmeraldas", type: "city", latlng: [0.7343619, -79.3858867] },
  { name: "Muisne", type: "city", latlng: [0.53872465, -79.8819717523788] },
  {
    name: "Atacames",
    type: "city",
    latlng: [0.79710855, -79.8790840742983]
  },
  { name: "Quinindé", type: "city", latlng: [0.328723, -79.4726416] },
  { name: "Tulcán", type: "city", latlng: [0.8119288, -77.7171076] },
  { name: "Mira", type: "city", latlng: [0.71702875, -78.1917880300142] },
  { name: "Espejo", type: "city", latlng: [0.713927, -77.96622643662] },
  {
    name: "Montúfar",
    type: "city",
    latlng: [0.56843345, -77.8087822387136]
  },
  {
    name: "San Pedro de Huaca",
    type: "city",
    latlng: [0.62336305, -77.7206361688019]
  },
  {
    name: "Bolívar (Carchi)",
    type: "city",
    latlng: [0.472635, -77.9358505135391]
  },
  { name: "Ibarra", type: "city", latlng: [0.3478421, -78.1173588] },
  {
    name: "San Miguel de Urcuquí",
    type: "city",
    latlng: [0.5777228, -78.3349090109371]
  },
  {
    name: "Cotacachi",
    type: "city",
    latlng: [0.3955234, -78.4507013183269]
  },
  { name: "Antonio Ante", type: "city", latlng: [0.3316022, -78.219615] },
  {
    name: "Otavalo",
    type: "city",
    latlng: [0.22276365, -78.2454274435196]
  },
  { name: "Pimampiro", type: "city", latlng: [0.3924612, -77.9402957] },
  { name: "Sucumbíos", type: "city", latlng: [-0.039138, -76.5119678] },
  {
    name: "Gonzalo Pizarro",
    type: "city",
    latlng: [0.1116857, -77.6398817070101]
  },
  { name: "Cascales", type: "city", latlng: [-2.2345295, -79.9101118] },
  {
    name: "Lago Agrio",
    type: "city",
    latlng: [0.12658025, -76.7576221148817]
  },
  {
    name: "Putumayo",
    type: "city",
    latlng: [0.13399195, -76.1307912006416]
  },
  { name: "Cuyabeno", type: "city", latlng: [-0.2655114, -75.8920692] },
  {
    name: "Shushufindi",
    type: "city",
    latlng: [-0.2886094, -76.5906631604309]
  },
  {
    name: "Pedernales",
    type: "city",
    latlng: [0.0689604, -79.8522466475605]
  },
  { name: "Chone", type: "city", latlng: [-0.38663675, -80.0522680333819] },
  {
    name: "Flavio Alfaro",
    type: "city",
    latlng: [-0.32428605, -79.8445363628985]
  },
  { name: "El Carmen", type: "city", latlng: [-2.1806779, -79.8789297] },
  { name: "Jama", type: "city", latlng: [-0.2149489, -80.2295833686682] },
  {
    name: "San Vicente",
    type: "city",
    latlng: [-0.4373946, -80.376140850306]
  },
  { name: "Sucre", type: "city", latlng: [-0.7264261, -80.4161951263733] },
  {
    name: "Tosagua",
    type: "city",
    latlng: [-0.77290475, -80.2556583356452]
  },
  {
    name: "Rocafuerte",
    type: "city",
    latlng: [-0.8990136, -80.4036370947811]
  },
  { name: "Junín", type: "city", latlng: [-0.9261304, -80.2055648] },
  {
    name: "Bolívar (Manabí)",
    type: "city",
    latlng: [-0.9292034, -80.0194710496605]
  },
  { name: "Pichincha", type: "city", latlng: [-0.0584893, -78.3284371] },
  { name: "Portoviejo", type: "city", latlng: [-1.0528066, -80.4534269] },
  {
    name: "Jaramijó",
    type: "city",
    latlng: [-0.9739097, -80.6020847675854]
  },
  { name: "Manta", type: "city", latlng: [-1.0321775, -80.8222166660983] },
  { name: "Montecristi", type: "city", latlng: [-1.0503675, -80.6608104] },
  {
    name: "Santa Ana",
    type: "city",
    latlng: [-1.1950113, -80.2648184045925]
  },
  {
    name: "Jipijapa",
    type: "city",
    latlng: [-1.5158193, -80.6124982491711]
  },
  {
    name: "Veinticuatro de Mayo",
    type: "city",
    latlng: [-1.3842713, -80.3391512292811]
  },
  {
    name: "Olmedo (Manabí)",
    type: "city",
    latlng: [-1.3950973, -80.2116429]
  },
  {
    name: "Puerto López",
    type: "city",
    latlng: [-1.5440814, -80.7445239577186]
  },
  { name: "Paján", type: "city", latlng: [-1.70407915, -80.4212974804798] },
  { name: "La Concordia", type: "city", latlng: [1.0216116, -79.0231981] },
  {
    name: "Santo Domingo",
    type: "city",
    latlng: [-0.25105, -79.063278]
  },
  {
    name: "Puerto Quito",
    type: "city",
    latlng: [0.1608869, -79.226730350178]
  },
  {
    name: "Pedro Vicente Maldonado",
    type: "city",
    latlng: [0.0864955, -79.052577]
  },
  {
    name: "San Miguel de Los Bancos",
    type: "city",
    latlng: [-0.01877705, -78.921723470876]
  },
  {
    name: "Quito",
    type: "city",
    latlng: [-0.215492, -78.5013647530921]
  },
  {
    name: "Pedro Moncayo",
    type: "city",
    latlng: [-0.2149977, -78.4968845]
  },
  {
    name: "Cayambe",
    type: "city",
    latlng: [-0.0038813, -78.0481800834448]
  },
  {
    name: "Rumiñahui",
    type: "city",
    latlng: [-0.39888, -78.4368106598363]
  },
  { name: "Mejía", type: "city", latlng: [-0.4984774, -78.6516914663548] },
  { name: "El Chaco", type: "city", latlng: [-0.3386642, -77.809851] },
  { name: "Quijos", type: "city", latlng: [-0.2222967, -78.5234313] },
  {
    name: "Archidona",
    type: "city",
    latlng: [-0.71607125, -77.9550419698538]
  },
  { name: "Tena", type: "city", latlng: [-0.94387175, -78.1083981523959] },
  {
    name: "Carlos Julio Arosemena Tola",
    type: "city",
    latlng: [-1.1673159, -77.9396895]
  },
  { name: "Loreto", type: "city", latlng: [-0.6471884, -77.3252737672724] },
  { name: "Orellana", type: "city", latlng: [-0.78618, -76.4803184] },
  {
    name: "La Joya de los Sachas",
    type: "city",
    latlng: [-0.2611097, -76.9080096240926]
  },
  {
    name: "Aguarico",
    type: "city",
    latlng: [-0.9846232, -75.989153346087]
  },
  { name: "Mera", type: "city", latlng: [-1.44628965, -78.1161912057428] },
  { name: "Santa Clara", type: "city", latlng: [-1.265062, -77.8874893] },
  {
    name: "Arajuno",
    type: "city",
    latlng: [-1.3537363, -76.8441100604156]
  },
  { name: "Pastaza", type: "city", latlng: [-1.916667, -77] },
  {
    name: "Buena Fe",
    type: "city",
    latlng: [-0.74829255, -79.5297122396171]
  },
  {
    name: "Valencia",
    type: "city",
    latlng: [-0.7696521, -79.3211355034001]
  },
  { name: "Quevedo", type: "city", latlng: [-1.0243508, -79.4663323] },
  { name: "Quinsaloma", type: "city", latlng: [-1.2065709, -79.3143312] },
  { name: "Palenque", type: "city", latlng: [-1.4374614, -79.7554206] },
  {
    name: "Mocache",
    type: "city",
    latlng: [-1.19756625, -79.5496158959561]
  },
  {
    name: "Ventanas",
    type: "city",
    latlng: [-1.3335519, -79.3369008695944]
  },
  { name: "Vinces", type: "city", latlng: [-1.5584095, -79.7509236] },
  { name: "Baba", type: "city", latlng: [-1.67300075, -79.659842362994] },
  { name: "Puebloviejo", type: "city", latlng: [-1.551415, -79.5329942] },
  {
    name: "Urdaneta",
    type: "city",
    latlng: [-1.56802025, -79.3769671833962]
  },
  { name: "Babahoyo", type: "city", latlng: [-1.8009022, -79.5338214] },
  {
    name: "Montalvo",
    type: "city",
    latlng: [-1.8016163, -79.3463655790142]
  },
  {
    name: "Sigchos",
    type: "city",
    latlng: [-0.61302145, -78.8818616273973]
  },
  {
    name: "La Maná",
    type: "city",
    latlng: [-0.78419085, -79.1010573360607]
  },
  { name: "Latacunga", type: "city", latlng: [-0.9336215, -78.6150488] },
  {
    name: "Saquisilí",
    type: "city",
    latlng: [-0.8359835, -78.7312623545021]
  },
  {
    name: "Pujilí",
    type: "city",
    latlng: [-1.00770465, -78.8559204588373]
  },
  {
    name: "Pangua",
    type: "city",
    latlng: [-1.10073805, -79.1641109407438]
  },
  {
    name: "Salcedo",
    type: "city",
    latlng: [-1.0570033, -78.5411138897882]
  },
  { name: "Guaranda", type: "city", latlng: [-1.5922898, -79.0015614] },
  { name: "Las Naves", type: "city", latlng: [-2.2131748, -79.9408317] },
  {
    name: "Echeandía",
    type: "city",
    latlng: [-1.44758925, -79.2738200852144]
  },
  { name: "Caluma", type: "city", latlng: [-1.6328007, -79.2601724] },
  { name: "Chimbo", type: "city", latlng: [-1.6771505, -79.130623867671] },
  {
    name: "San Miguel",
    type: "city",
    latlng: [-1.81214325, -79.1277096099702]
  },
  { name: "Chillanes", type: "city", latlng: [-1.9432708, -79.0660695] },
  { name: "Ambato", type: "city", latlng: [-1.2422461, -78.6292567] },
  { name: "Píllaro", type: "city", latlng: [-1.1725302, -78.5423177] },
  {
    name: "Patate",
    type: "city",
    latlng: [-1.28316955, -78.4297644244115]
  },
  { name: "Baños", type: "city", latlng: [-1.33191575, -78.2625285385066] },
  { name: "Pelileo", type: "city", latlng: [-1.3293759, -78.545843] },
  {
    name: "Cevallos",
    type: "city",
    latlng: [-1.34957625, -78.6155503772334]
  },
  { name: "Tisaleo", type: "city", latlng: [-1.3489002, -78.6693045] },
  { name: "Mocha", type: "city", latlng: [-1.4191002, -78.6615418] },
  { name: "Quero", type: "city", latlng: [-1.4300614, -78.6083724473263] },
  { name: "Guano", type: "city", latlng: [-1.539923, -78.657838509137] },
  { name: "Penipe", type: "city", latlng: [-1.5659281, -78.5315515] },
  { name: "Riobamba", type: "city", latlng: [-1.6731483, -78.6486465] },
  { name: "Colta", type: "city", latlng: [-1.79709295, -78.8494368104333] },
  { name: "Chambo", type: "city", latlng: [-1.7320661, -78.5951097] },
  {
    name: "Pallatanga",
    type: "city",
    latlng: [-2.0167297, -78.9302292966983]
  },
  {
    name: "Guamote",
    type: "city",
    latlng: [-2.04933205, -78.6426692558071]
  },
  { name: "Alausí", type: "city", latlng: [-2.3250494, -78.7035660061718] },
  { name: "Cumandá", type: "city", latlng: [-1.4595105, -78.1366323] },
  {
    name: "Chunchi",
    type: "city",
    latlng: [-2.3285653, -78.8932832085705]
  },
  { name: "Palora", type: "city", latlng: [-1.6981449, -77.9675269] },
  {
    name: "Pablo Sexto",
    type: "city",
    latlng: [-1.84087685, -78.2913468548678]
  },
  { name: "Huamboya", type: "city", latlng: [-1.9464404, -77.9904213] },
  { name: "Morona", type: "city", latlng: [-2.4303097, -77.880192010847] },
  { name: "Taisha", type: "city", latlng: [-2.3812385, -77.5087463] },
  { name: "Sucúa", type: "city", latlng: [-2.4560169, -78.1726625] },
  { name: "Santiago", type: "city", latlng: [-3.7939049, -79.2817721] },
  { name: "Logroño", type: "city", latlng: [-2.6259898, -78.19963] },
  { name: "Tiwintza", type: "city", latlng: [0.9157101, -79.6900499] },
  {
    name: "Limón Indanza",
    type: "city",
    latlng: [-2.9663139, -78.4308133]
  },
  {
    name: "San Juan Bosco",
    type: "city",
    latlng: [-3.1183356, -78.5300164]
  },
  {
    name: "Gualaquiza",
    type: "city",
    latlng: [-3.32765065, -78.7064901988385]
  },
  {
    name: "El Empalme",
    type: "city",
    latlng: [-0.9910107, -79.6642365343776]
  },
  { name: "Balzar", type: "city", latlng: [-1.3063032, -79.9349766799729] },
  { name: "Colimes", type: "city", latlng: [-1.5456483, -80.010547] },
  { name: "Palestina", type: "city", latlng: [-1.629117, -79.9807392] },
  { name: "Santa Lucía", type: "city", latlng: [-1.7162715, -79.9849423] },
  {
    name: "Pedro Carbo",
    type: "city",
    latlng: [-1.86726715, -80.3009813925023]
  },
  { name: "Isidro Ayora", type: "city", latlng: [-1.8796754, -80.1463096] },
  {
    name: "Lomas de Sargentillo",
    type: "city",
    latlng: [-1.8808196, -80.0814602]
  },
  { name: "Nobol", type: "city", latlng: [-1.98178425, -80.0670669917175] },
  { name: "Daule", type: "city", latlng: [-1.92000685, -79.9129306979237] },
  { name: "Salitre", type: "city", latlng: [-1.8293674, -79.8174167] },
  {
    name: "Samborondón",
    type: "city",
    latlng: [-2.0153601, -79.7340649369068]
  },
  { name: "Yaguachi", type: "city", latlng: [-0.2173326, -78.4960519] },
  {
    name: "Alfredo Baquerizo Moreno",
    type: "city",
    latlng: [-1.9494337, -79.5495282851817]
  },
  {
    name: "Milagro",
    type: "city",
    latlng: [-2.11717955, -79.5566606071334]
  },
  {
    name: "Simón Bolívar",
    type: "city",
    latlng: [-2.0424101, -79.4218633880435]
  },
  { name: "Naranjito", type: "city", latlng: [-2.1685398, -79.4630975] },
  {
    name: "General Antonio Elizalde",
    type: "city",
    latlng: [-2.1429197, -79.2261264625635]
  },
  {
    name: "Coronel Marcelino Maridueña",
    type: "city",
    latlng: [-2.23336285, -79.3861649354298]
  },
  {
    name: "El Triunfo",
    type: "city",
    latlng: [-2.2907784, -79.3660575197123]
  },
  { name: "Durán", type: "city", latlng: [-2.215207, -79.7903725123108] },
  { name: "Guayaquil", type: "city", latlng: [-2.1899066, -79.887726] },
  { name: "Playas", type: "city", latlng: [-2.6356397, -80.3913928] },
  {
    name: "Naranjal",
    type: "city",
    latlng: [-2.5731305, -79.5325633959898]
  },
  { name: "Balao", type: "city", latlng: [-2.88525165, -79.7180498428229] },
  { name: "Santa Elena", type: "city", latlng: [-2.1954593, -80.5669167] },
  { name: "La Libertad", type: "city", latlng: [-0.2209692, -78.5253239] },
  { name: "Salinas", type: "city", latlng: [-2.2073132, -80.9684566] },
  {
    name: "La Troncal",
    type: "city",
    latlng: [-2.433689, -79.3751632252655]
  },
  { name: "Cañar", type: "city", latlng: [-2.574484, -78.9804678] },
  { name: "Suscal", type: "city", latlng: [-2.4378607, -79.0511208] },
  {
    name: "El Tambo",
    type: "city",
    latlng: [-2.48280785, -78.9124904150334]
  },
  {
    name: "Azogues",
    type: "city",
    latlng: [-2.63172915, -78.7008934064092]
  },
  { name: "Biblián", type: "city", latlng: [-2.7101694, -78.8908362] },
  { name: "Déleg", type: "city", latlng: [-2.7652918, -78.9300169745813] },
  {
    name: "Sevilla de Oro",
    type: "city",
    latlng: [-2.7051608, -78.6014387016541]
  },
  { name: "Paute", type: "city", latlng: [-2.7461213, -78.7364807424428] },
  {
    name: "Guachapala",
    type: "city",
    latlng: [-2.76681085, -78.7113488321396]
  },
  {
    name: "El Pan",
    type: "city",
    latlng: [-2.83830715, -78.6539290478538]
  },
  {
    name: "Gualaceo",
    type: "city",
    latlng: [-2.9179486, -78.8461880140753]
  },
  {
    name: "Chordeleg",
    type: "city",
    latlng: [-2.98446755, -78.7307217577724]
  },
  {
    name: "Sígsig",
    type: "city",
    latlng: [-3.13308835, -78.8733115387591]
  },
  { name: "Cuenca", type: "city", latlng: [-2.8973745, -79.0044518] },
  {
    name: "Santa Isabel",
    type: "city",
    latlng: [-3.1839392, -79.3594117719649]
  },
  { name: "Pucará", type: "city", latlng: [-3.1748913, -79.514712723913] },
  {
    name: "Camilo Ponce Enríquez",
    type: "city",
    latlng: [-2.96406955, -79.5630363473857]
  },
  {
    name: "San Fernando",
    type: "city",
    latlng: [-3.1398718, -79.2794840907109]
  },
  { name: "Girón", type: "city", latlng: [-3.1728573, -79.1241595798571] },
  { name: "Nabón", type: "city", latlng: [-3.33282635, -79.0996575205106] },
  { name: "Oña", type: "city", latlng: [-3.49630835, -79.1092822740485] },
  { name: "El Guabo", type: "city", latlng: [-3.2413487, -79.8296713] },
  { name: "Machala", type: "city", latlng: [-3.2587494, -79.9595133] },
  { name: "Pasaje", type: "city", latlng: [-0.253255, -79.167726] },
  { name: "Chilla", type: "city", latlng: [-3.4584322, -79.5809951] },
  {
    name: "Zaruma",
    type: "city",
    latlng: [-3.50855405, -79.5108420957504]
  },
  {
    name: "Santa Rosa",
    type: "city",
    latlng: [-3.41455385, -80.1549039658861]
  },
  {
    name: "Atahualpa",
    type: "city",
    latlng: [-3.5572055, -79.7098882225052]
  },
  {
    name: "Arenillas",
    type: "city",
    latlng: [-3.57396445, -80.0986007104956]
  },
  {
    name: "Huaquillas",
    type: "city",
    latlng: [-3.45925155, -80.1820737271825]
  },
  { name: "Las Lajas", type: "city", latlng: [-3.7848952, -80.0636021] },
  { name: "Marcabelí", type: "city", latlng: [-3.7844549, -79.9113661] },
  { name: "Balsas", type: "city", latlng: [-3.7742179, -79.8310960432725] },
  { name: "Piñas", type: "city", latlng: [-3.71088185, -79.7837851533668] },
  { name: "Portovelo", type: "city", latlng: [-3.7154781, -79.6167189] },
  { name: "Saraguro", type: "city", latlng: [-3.6224722, -79.2384881] },
  { name: "Loja", type: "city", latlng: [-4.0528506, -79.8053425] },
  { name: "Chaguarpamba", type: "city", latlng: [-3.876542, -79.6446609] },
  {
    name: "Olmedo (Loja)",
    type: "city",
    latlng: [-3.9345786, -79.6468514]
  },
  {
    name: "Catamayo",
    type: "city",
    latlng: [-3.9911481, -79.4038821570871]
  },
  { name: "Paltas", type: "city", latlng: [-4.0026163, -79.7006645141133] },
  {
    name: "Puyango",
    type: "city",
    latlng: [-3.96534605, -80.073541407174]
  },
  { name: "Pindal", type: "city", latlng: [-4.1150962, -80.1079721] },
  {
    name: "Celica",
    type: "city",
    latlng: [-4.16481365, -79.9851761755119]
  },
  {
    name: "Zapotillo",
    type: "city",
    latlng: [-4.2334403, -80.2893109657604]
  },
  { name: "Macará", type: "city", latlng: [-4.3490568, -79.9161416249543] },
  { name: "Sozoranga", type: "city", latlng: [-4.3268672, -79.7902815] },
  { name: "Calvas", type: "city", latlng: [-4.4799894, -79.51516] },
  { name: "Gonzanamá", type: "city", latlng: [-4.2302729, -79.4365638] },
  { name: "Quilanga", type: "city", latlng: [-4.297155, -79.401265] },
  { name: "Espíndola", type: "city", latlng: [-4.3268185, -79.5525718] },
  { name: "Yacuambi", type: "city", latlng: [-2.1349894, -79.8960421] },
  {
    name: "Yantzaza",
    type: "city",
    latlng: [-3.71468115, -78.7376344463973]
  },
  { name: "El Pangui", type: "city", latlng: [-3.625, -78.5869441] },
  { name: "Zamora", type: "city", latlng: [-4.0042008, -78.9577754191094] },
  {
    name: "Centinela del Cóndor",
    type: "city",
    latlng: [-0.74796, -79.19933]
  },
  { name: "Paquisha", type: "city", latlng: [-3.9336811, -78.6757723] },
  { name: "Nangaritza", type: "city", latlng: [-4.0604355, -78.963136] },
  { name: "Palanda", type: "city", latlng: [-4.6498277, -79.1318589] },
  {
    name: "Chinchipe",
    type: "city",
    latlng: [-4.8536233, -79.1377245683016]
  },
  { name: "Isabela", type: "city", latlng: [-2.8959964, -79.0193947] },
  {
    name: "San Cristóbal",
    type: "city",
    latlng: [-0.9151226, -89.5662955]
  },
  {
    name: "Santa Cruz",
    type: "city",
    latlng: [-0.62881505, -90.3638752022324]
  }
]