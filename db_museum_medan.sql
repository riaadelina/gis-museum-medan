--
-- PostgreSQL database dump
--

\restrict hcPD0bFTqw8bCHf7ndqpxpxoQoSuvV1yr46Bi2QoHuwvIJ0wunrQ6u3S70Tdu43

-- Dumped from database version 18.3
-- Dumped by pg_dump version 18.3

-- Started on 2026-06-09 22:39:21

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 2 (class 3079 OID 51659)
-- Name: postgis; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis WITH SCHEMA public;


--
-- TOC entry 5979 (class 0 OID 0)
-- Dependencies: 2
-- Name: EXTENSION postgis; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION postgis IS 'PostGIS geometry and geography spatial types and functions';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 228 (class 1259 OID 52762)
-- Name: kategori_museum; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.kategori_museum (
    id integer NOT NULL,
    nama_kategori character varying(100) NOT NULL,
    deskripsi text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.kategori_museum OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 52761)
-- Name: kategori_museum_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.kategori_museum_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.kategori_museum_id_seq OWNER TO postgres;

--
-- TOC entry 5980 (class 0 OID 0)
-- Dependencies: 227
-- Name: kategori_museum_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.kategori_museum_id_seq OWNED BY public.kategori_museum.id;


--
-- TOC entry 230 (class 1259 OID 52774)
-- Name: museum; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.museum (
    id integer NOT NULL,
    nama character varying(200) NOT NULL,
    alamat text NOT NULL,
    deskripsi text,
    telepon character varying(20),
    jam_buka character varying(100),
    harga_tiket character varying(100),
    id_kategori integer NOT NULL,
    geom public.geometry(Point,4326) NOT NULL,
    foto character varying(255),
    status character varying(20) DEFAULT 'aktif'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_latitude CHECK (((public.st_y(geom) >= (3.0)::double precision) AND (public.st_y(geom) <= (4.0)::double precision))),
    CONSTRAINT chk_longitude CHECK (((public.st_x(geom) >= (98.0)::double precision) AND (public.st_x(geom) <= (99.0)::double precision)))
);


ALTER TABLE public.museum OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 52773)
-- Name: museum_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.museum_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.museum_id_seq OWNER TO postgres;

--
-- TOC entry 5981 (class 0 OID 0)
-- Dependencies: 229
-- Name: museum_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.museum_id_seq OWNED BY public.museum.id;


--
-- TOC entry 232 (class 1259 OID 52805)
-- Name: mv_statistik_museum; Type: MATERIALIZED VIEW; Schema: public; Owner: postgres
--

CREATE MATERIALIZED VIEW public.mv_statistik_museum AS
 SELECT k.nama_kategori,
    count(m.id) AS jumlah_museum,
    public.st_astext(public.st_centroid(public.st_collect(m.geom))) AS pusat_sebaran
   FROM (public.museum m
     JOIN public.kategori_museum k ON ((m.id_kategori = k.id)))
  WHERE ((m.status)::text = 'aktif'::text)
  GROUP BY k.id, k.nama_kategori
  WITH NO DATA;


ALTER MATERIALIZED VIEW public.mv_statistik_museum OWNER TO postgres;

--
-- TOC entry 226 (class 1259 OID 52748)
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id integer NOT NULL,
    username character varying(50) NOT NULL,
    password character varying(255) NOT NULL,
    role character varying(20) DEFAULT 'admin'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.users OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 52747)
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- TOC entry 5982 (class 0 OID 0)
-- Dependencies: 225
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 231 (class 1259 OID 52800)
-- Name: v_museum_lengkap; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.v_museum_lengkap AS
 SELECT m.id,
    m.nama,
    m.alamat,
    m.deskripsi,
    m.telepon,
    m.jam_buka,
    m.harga_tiket,
    m.foto,
    m.status,
    k.nama_kategori AS kategori,
    m.id_kategori,
    public.st_y(m.geom) AS latitude,
    public.st_x(m.geom) AS longitude,
    public.st_asgeojson(m.geom) AS geojson,
    m.created_at,
    m.updated_at
   FROM (public.museum m
     JOIN public.kategori_museum k ON ((m.id_kategori = k.id)))
  WHERE ((m.status)::text = 'aktif'::text);


ALTER VIEW public.v_museum_lengkap OWNER TO postgres;

--
-- TOC entry 5790 (class 2604 OID 52765)
-- Name: kategori_museum id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.kategori_museum ALTER COLUMN id SET DEFAULT nextval('public.kategori_museum_id_seq'::regclass);


--
-- TOC entry 5792 (class 2604 OID 52777)
-- Name: museum id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.museum ALTER COLUMN id SET DEFAULT nextval('public.museum_id_seq'::regclass);


--
-- TOC entry 5787 (class 2604 OID 52751)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 5970 (class 0 OID 52762)
-- Dependencies: 228
-- Data for Name: kategori_museum; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.kategori_museum (id, nama_kategori, deskripsi, created_at) FROM stdin;
1	Sejarah	Museum yang menyimpan koleksi benda-benda bersejarah	2026-05-10 11:31:46.073443
2	Seni	Museum yang menampilkan karya seni dan budaya	2026-05-10 11:31:46.073443
3	Edukasi	Museum yang berfokus pada pendidikan dan ilmu pengetahuan	2026-05-10 11:31:46.073443
4	Alam	Museum yang menampilkan koleksi alam dan lingkungan	2026-05-10 11:31:46.073443
5	Militer	Museum yang menyimpan koleksi peralatan dan sejarah militer	2026-05-10 11:31:46.073443
\.


--
-- TOC entry 5972 (class 0 OID 52774)
-- Dependencies: 230
-- Data for Name: museum; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.museum (id, nama, alamat, deskripsi, telepon, jam_buka, harga_tiket, id_kategori, geom, foto, status, created_at, updated_at) FROM stdin;
1	Museum Negeri Sumatera Utara	Jl. HM Joni No.51A, Medan	Museum terbesar di Sumatera Utara yang menyimpan berbagai koleksi sejarah, budaya, arkeologi, dan etnografi dari seluruh wilayah Sumatera Utara.	061-7364759	Selasa-Minggu 08:00-16:00	Rp 5.000	1	0101000020E6100000728A8EE4F2AB5840E9263108AC9C0C40	\N	aktif	2026-05-10 12:03:06.605432	2026-05-10 12:03:06.605432
2	Museum Rahmat International Wildlife Museum & Gallery	Jl. S. Parman No.309, Medan	Museum satwa liar internasional dengan ribuan koleksi spesimen hewan dari berbagai negara yang digunakan untuk edukasi dan konservasi.	061-4556517	Senin-Minggu 09:00-17:00	Rp 35.000	4	0101000020E61000005F07CE1951AA584052499D8026C20C40	\N	aktif	2026-05-10 12:03:06.605432	2026-05-10 12:03:06.605432
3	Museum Perjuangan TNI	Jl. Zainul Arifin No.8, Medan	Museum yang menyimpan koleksi sejarah perjuangan Tentara Nasional Indonesia di Sumatera Utara.	061-4518116	Senin-Jumat 08:00-14:00	Gratis	5	0101000020E610000057EC2FBB27AB5840F46C567DAEB60C40	\N	aktif	2026-05-10 12:03:06.605432	2026-05-10 12:03:06.605432
4	Tjong A Fie Mansion	Jl. Jenderal Ahmad Yani No.105, Medan	Rumah bersejarah milik Tjong A Fie yang menjadi salah satu ikon heritage Kota Medan dengan arsitektur perpaduan Tionghoa dan kolonial.	061-4519700	Setiap Hari 09:00-17:00	Rp 35.000	1	0101000020E61000004182E2C798AB5840E71DA7E848AE0C40	\N	aktif	2026-05-10 12:03:06.605432	2026-05-10 12:03:06.605432
5	Museum Perkebunan Indonesia	Jl. Brigjen Katamso No.53, Medan	Museum yang menampilkan sejarah perkembangan industri perkebunan di Indonesia khususnya Sumatera Utara sejak masa kolonial.	061-7862038	Senin-Jumat 08:00-16:00	Rp 10.000	3	0101000020E61000009EEFA7C64BAB5840F38E537424970C40	\N	aktif	2026-05-10 12:03:06.605432	2026-05-10 12:03:06.605432
6	Istana Maimun	Jl. Brigjen Katamso No.66, Medan	Istana peninggalan Kesultanan Deli yang terkenal dengan perpaduan arsitektur Melayu, Islam, Eropa, dan Timur Tengah.	061-4513548	Setiap Hari 08:00-17:00	Rp 10.000	1	0101000020E61000009E5E29CB10AB58400B462575029A0C40	\N	aktif	2026-05-10 12:03:06.605432	2026-05-10 12:03:06.605432
8	Museum Sejarah Al-Qur'an Sumatera Utara	Jl. Willem Iskandar, Medan	Museum religi yang menyimpan koleksi sejarah penulisan dan perkembangan Al-Qur'an di Sumatera Utara.	-	Senin-Sabtu 09:00-16:00	Gratis	2	0101000020E610000075029A081BAE584046B6F3FDD4F80C40	\N	aktif	2026-05-10 12:03:06.605432	2026-05-10 12:03:06.605432
9	Pos Bloc Medan	Jl. Balai Kota No.1, Medan	Bangunan kantor pos bersejarah yang direvitalisasi menjadi pusat kreatif, budaya, dan ruang publik modern di Kota Medan.	-	Setiap Hari 10:00-22:00	Gratis	2	0101000020E610000096218E7571AB58408048BF7D1DB80C40	\N	aktif	2026-05-10 12:03:06.605432	2026-05-10 12:03:06.605432
10	Museum Perjuangan Pers Sumatera Utara	Jl. Adinegoro No.4, Medan	Museum yang menyimpan dokumentasi dan sejarah perkembangan pers serta jurnalistik di Sumatera Utara.	-	Senin-Jumat 08:00-15:00	Gratis	5	0101000020E6100000006F8104C5AB5840083D9B559FAB0C40	\N	aktif	2026-05-10 12:03:06.605432	2026-05-10 12:03:06.605432
7	Gedung Juang 45	Jl. Pemuda No.17, Medan	Gedung bersejarah yang menjadi saksi perjuangan rakyat Indonesia pada masa kemerdekaan di Kota Medan.	-	Senin-Sabtu 08:00-16:00	Gratis	5	0101000020E61000002B8716D9CEAB58408AB0E1E995B20C40	\N	aktif	2026-05-10 12:03:06.605432	2026-05-10 12:33:04.505412
\.


--
-- TOC entry 5786 (class 0 OID 51978)
-- Dependencies: 221
-- Data for Name: spatial_ref_sys; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.spatial_ref_sys (srid, auth_name, auth_srid, srtext, proj4text) FROM stdin;
\.


--
-- TOC entry 5968 (class 0 OID 52748)
-- Dependencies: 226
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, username, password, role, created_at) FROM stdin;
1	admin	$2y$10$JU9r1nPXVFb0QyJSoa5J7.5kT04LLcAC/ZCiHHX7NeytEQec3t6Fm	admin	2026-05-10 11:29:43.129711
\.


--
-- TOC entry 5983 (class 0 OID 0)
-- Dependencies: 227
-- Name: kategori_museum_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.kategori_museum_id_seq', 5, true);


--
-- TOC entry 5984 (class 0 OID 0)
-- Dependencies: 229
-- Name: museum_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.museum_id_seq', 11, true);


--
-- TOC entry 5985 (class 0 OID 0)
-- Dependencies: 225
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 1, true);


--
-- TOC entry 5806 (class 2606 OID 52772)
-- Name: kategori_museum kategori_museum_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.kategori_museum
    ADD CONSTRAINT kategori_museum_pkey PRIMARY KEY (id);


--
-- TOC entry 5811 (class 2606 OID 52791)
-- Name: museum museum_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.museum
    ADD CONSTRAINT museum_pkey PRIMARY KEY (id);


--
-- TOC entry 5802 (class 2606 OID 52758)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 5804 (class 2606 OID 52760)
-- Name: users users_username_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_key UNIQUE (username);


--
-- TOC entry 5807 (class 1259 OID 52797)
-- Name: idx_museum_geom; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_museum_geom ON public.museum USING gist (geom);


--
-- TOC entry 5808 (class 1259 OID 52798)
-- Name: idx_museum_kategori; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_museum_kategori ON public.museum USING btree (id_kategori);


--
-- TOC entry 5809 (class 1259 OID 52799)
-- Name: idx_museum_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_museum_status ON public.museum USING btree (status);


--
-- TOC entry 5812 (class 2606 OID 52792)
-- Name: museum museum_id_kategori_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.museum
    ADD CONSTRAINT museum_id_kategori_fkey FOREIGN KEY (id_kategori) REFERENCES public.kategori_museum(id);


--
-- TOC entry 5973 (class 0 OID 52805)
-- Dependencies: 232 5975
-- Name: mv_statistik_museum; Type: MATERIALIZED VIEW DATA; Schema: public; Owner: postgres
--

REFRESH MATERIALIZED VIEW public.mv_statistik_museum;


-- Completed on 2026-06-09 22:39:21

--
-- PostgreSQL database dump complete
--

\unrestrict hcPD0bFTqw8bCHf7ndqpxpxoQoSuvV1yr46Bi2QoHuwvIJ0wunrQ6u3S70Tdu43

