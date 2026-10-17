--
-- PostgreSQL database dump
--


-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

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

ALTER TABLE IF EXISTS ONLY public.payments DROP CONSTRAINT IF EXISTS payments_bill_id_fkey;
ALTER TABLE IF EXISTS ONLY public.patients DROP CONSTRAINT IF EXISTS patients_user_id_fkey;
ALTER TABLE IF EXISTS ONLY public.notifications DROP CONSTRAINT IF EXISTS notifications_patient_id_fkey;
ALTER TABLE IF EXISTS ONLY public.lab_results DROP CONSTRAINT IF EXISTS lab_results_released_by_fkey;
ALTER TABLE IF EXISTS ONLY public.doctors DROP CONSTRAINT IF EXISTS doctors_user_id_fkey;
ALTER TABLE IF EXISTS ONLY public.doctor_weekly_availability DROP CONSTRAINT IF EXISTS doctor_weekly_availability_doctor_id_fkey;
ALTER TABLE IF EXISTS ONLY public.doctor_schedule_blocks DROP CONSTRAINT IF EXISTS doctor_schedule_blocks_doctor_id_fkey;
ALTER TABLE IF EXISTS ONLY public.doctor_schedule_blocks DROP CONSTRAINT IF EXISTS doctor_schedule_blocks_created_by_fkey;
ALTER TABLE IF EXISTS ONLY public.bills DROP CONSTRAINT IF EXISTS bills_appointment_id_fkey;
ALTER TABLE IF EXISTS ONLY public.appointments DROP CONSTRAINT IF EXISTS appointments_patient_id_fkey;
ALTER TABLE IF EXISTS ONLY public.appointments DROP CONSTRAINT IF EXISTS appointments_doctor_id_fkey;
ALTER TABLE IF EXISTS ONLY public.appointments DROP CONSTRAINT IF EXISTS appointments_approved_by_fkey;
ALTER TABLE IF EXISTS ONLY public.appointment_details DROP CONSTRAINT IF EXISTS appointment_details_test_id_fkey;
ALTER TABLE IF EXISTS ONLY public.appointment_details DROP CONSTRAINT IF EXISTS appointment_details_appointment_id_fkey;
DROP INDEX IF EXISTS public.idx_schedule_blocks_doctor_date;
DROP INDEX IF EXISTS public.idx_notifications_patient;
DROP INDEX IF EXISTS public.idx_appointments_patient;
DROP INDEX IF EXISTS public.idx_appointments_doctor_date;
DROP INDEX IF EXISTS public.idx_appointment_details_appointment;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_pkey;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_email_key;
ALTER TABLE IF EXISTS ONLY public.payments DROP CONSTRAINT IF EXISTS payments_pkey;
ALTER TABLE IF EXISTS ONLY public.patients DROP CONSTRAINT IF EXISTS patients_user_id_key;
ALTER TABLE IF EXISTS ONLY public.patients DROP CONSTRAINT IF EXISTS patients_pkey;
ALTER TABLE IF EXISTS ONLY public.patients DROP CONSTRAINT IF EXISTS patients_email_key;
ALTER TABLE IF EXISTS ONLY public.otp_codes DROP CONSTRAINT IF EXISTS otp_codes_pkey;
ALTER TABLE IF EXISTS ONLY public.notifications DROP CONSTRAINT IF EXISTS notifications_pkey;
ALTER TABLE IF EXISTS ONLY public.laboratory_tests DROP CONSTRAINT IF EXISTS laboratory_tests_test_name_key;
ALTER TABLE IF EXISTS ONLY public.laboratory_tests DROP CONSTRAINT IF EXISTS laboratory_tests_pkey;
ALTER TABLE IF EXISTS ONLY public.lab_results DROP CONSTRAINT IF EXISTS lab_results_pkey;
ALTER TABLE IF EXISTS ONLY public.doctors DROP CONSTRAINT IF EXISTS doctors_user_id_key;
ALTER TABLE IF EXISTS ONLY public.doctors DROP CONSTRAINT IF EXISTS doctors_pkey;
ALTER TABLE IF EXISTS ONLY public.doctors DROP CONSTRAINT IF EXISTS doctors_email_key;
ALTER TABLE IF EXISTS ONLY public.doctor_weekly_availability DROP CONSTRAINT IF EXISTS doctor_weekly_availability_pkey;
ALTER TABLE IF EXISTS ONLY public.doctor_weekly_availability DROP CONSTRAINT IF EXISTS doctor_weekly_availability_doctor_id_day_of_week_start_time_key;
ALTER TABLE IF EXISTS ONLY public.doctor_schedule_blocks DROP CONSTRAINT IF EXISTS doctor_schedule_blocks_pkey;
ALTER TABLE IF EXISTS ONLY public.bills DROP CONSTRAINT IF EXISTS bills_pkey;
ALTER TABLE IF EXISTS ONLY public.bills DROP CONSTRAINT IF EXISTS bills_appointment_id_key;
ALTER TABLE IF EXISTS ONLY public.audit_logs DROP CONSTRAINT IF EXISTS audit_logs_pkey;
ALTER TABLE IF EXISTS ONLY public.appointments DROP CONSTRAINT IF EXISTS appointments_pkey;
ALTER TABLE IF EXISTS ONLY public.appointment_details DROP CONSTRAINT IF EXISTS appointment_details_pkey;
ALTER TABLE IF EXISTS ONLY public.appointment_details DROP CONSTRAINT IF EXISTS appointment_details_appointment_id_test_id_key;
ALTER TABLE IF EXISTS ONLY public.announcements DROP CONSTRAINT IF EXISTS announcements_pkey;
ALTER TABLE IF EXISTS public.otp_codes ALTER COLUMN otp_id DROP DEFAULT;
ALTER TABLE IF EXISTS public.audit_logs ALTER COLUMN log_id DROP DEFAULT;
ALTER TABLE IF EXISTS public.announcements ALTER COLUMN announcement_id DROP DEFAULT;
DROP TABLE IF EXISTS public.users;
DROP TABLE IF EXISTS public.payments;
DROP TABLE IF EXISTS public.patients;
DROP SEQUENCE IF EXISTS public.otp_codes_otp_id_seq;
DROP TABLE IF EXISTS public.otp_codes;
DROP TABLE IF EXISTS public.notifications;
DROP TABLE IF EXISTS public.laboratory_tests;
DROP TABLE IF EXISTS public.lab_results;
DROP TABLE IF EXISTS public.doctors;
DROP TABLE IF EXISTS public.doctor_weekly_availability;
DROP TABLE IF EXISTS public.doctor_schedule_blocks;
DROP TABLE IF EXISTS public.bills;
DROP SEQUENCE IF EXISTS public.audit_logs_log_id_seq;
DROP TABLE IF EXISTS public.audit_logs;
DROP TABLE IF EXISTS public.appointments;
DROP TABLE IF EXISTS public.appointment_details;
DROP SEQUENCE IF EXISTS public.announcements_announcement_id_seq;
DROP TABLE IF EXISTS public.announcements;
SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: announcements; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.announcements (
    announcement_id integer NOT NULL,
    title character varying(255) NOT NULL,
    description text NOT NULL,
    image_url character varying(255),
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);



--
-- Name: announcements_announcement_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.announcements_announcement_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;



--
-- Name: announcements_announcement_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.announcements_announcement_id_seq OWNED BY public.announcements.announcement_id;


--
-- Name: appointment_details; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.appointment_details (
    appointment_detail_id integer NOT NULL,
    appointment_id integer NOT NULL,
    test_id integer NOT NULL,
    price_at_booking numeric(10,2) NOT NULL,
    CONSTRAINT appointment_details_price_at_booking_check CHECK ((price_at_booking >= (0)::numeric))
);



--
-- Name: appointment_details_appointment_detail_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.appointment_details ALTER COLUMN appointment_detail_id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.appointment_details_appointment_detail_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: appointments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.appointments (
    appointment_id integer NOT NULL,
    patient_id integer NOT NULL,
    doctor_id integer,
    appointment_date date NOT NULL,
    appointment_time time without time zone NOT NULL,
    status character varying(20) DEFAULT 'Pending'::character varying NOT NULL,
    notes character varying(255),
    approved_by integer,
    approved_at timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    expected_result_date date,
    CONSTRAINT appointments_status_check CHECK (((status)::text = ANY ((ARRAY['Pending'::character varying, 'Confirmed'::character varying, 'Completed'::character varying, 'Cancelled'::character varying])::text[])))
);



--
-- Name: appointments_appointment_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.appointments ALTER COLUMN appointment_id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.appointments_appointment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.audit_logs (
    log_id integer NOT NULL,
    user_id integer,
    action character varying(255) NOT NULL,
    details text,
    ip_address character varying(45),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);



--
-- Name: audit_logs_log_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.audit_logs_log_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;



--
-- Name: audit_logs_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.audit_logs_log_id_seq OWNED BY public.audit_logs.log_id;


--
-- Name: bills; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bills (
    bill_id integer NOT NULL,
    appointment_id integer NOT NULL,
    total_amount numeric(10,2) NOT NULL,
    status character varying(20) DEFAULT 'Unpaid'::character varying NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT bills_status_check CHECK (((status)::text = ANY ((ARRAY['Unpaid'::character varying, 'Partially Paid'::character varying, 'Paid'::character varying])::text[]))),
    CONSTRAINT bills_total_amount_check CHECK ((total_amount >= (0)::numeric))
);



--
-- Name: bills_bill_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.bills ALTER COLUMN bill_id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.bills_bill_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: doctor_schedule_blocks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.doctor_schedule_blocks (
    schedule_block_id integer NOT NULL,
    doctor_id integer NOT NULL,
    schedule_date date NOT NULL,
    start_time time without time zone NOT NULL,
    end_time time without time zone NOT NULL,
    block_type character varying(20) NOT NULL,
    status character varying(30) DEFAULT 'Awaiting doctor'::character varying NOT NULL,
    note character varying(255),
    created_by integer,
    confirmed_at timestamp without time zone,
    CONSTRAINT doctor_schedule_blocks_block_type_check CHECK (((block_type)::text = ANY ((ARRAY['Morning'::character varying, 'Afternoon'::character varying, 'Whole Day'::character varying, 'Individual'::character varying])::text[]))),
    CONSTRAINT doctor_schedule_blocks_check CHECK ((start_time < end_time)),
    CONSTRAINT doctor_schedule_blocks_status_check CHECK (((status)::text = ANY ((ARRAY['Awaiting doctor'::character varying, 'Confirmed'::character varying, 'Declined'::character varying])::text[])))
);



--
-- Name: doctor_schedule_blocks_schedule_block_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.doctor_schedule_blocks ALTER COLUMN schedule_block_id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.doctor_schedule_blocks_schedule_block_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: doctor_weekly_availability; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.doctor_weekly_availability (
    weekly_availability_id integer NOT NULL,
    doctor_id integer NOT NULL,
    day_of_week smallint NOT NULL,
    start_time time without time zone NOT NULL,
    end_time time without time zone NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    CONSTRAINT doctor_weekly_availability_check CHECK ((start_time < end_time)),
    CONSTRAINT doctor_weekly_availability_day_of_week_check CHECK (((day_of_week >= 0) AND (day_of_week <= 6)))
);



--
-- Name: doctor_weekly_availability_weekly_availability_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.doctor_weekly_availability ALTER COLUMN weekly_availability_id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.doctor_weekly_availability_weekly_availability_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: doctors; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.doctors (
    doctor_id integer NOT NULL,
    user_id integer,
    first_name character varying(100) NOT NULL,
    last_name character varying(100) NOT NULL,
    email character varying(255),
    phone character varying(30),
    specialization character varying(150) NOT NULL,
    profile_picture character varying(255),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);



--
-- Name: doctors_doctor_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.doctors ALTER COLUMN doctor_id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.doctors_doctor_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: lab_results; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.lab_results (
    lab_result_id integer NOT NULL,
    file_path character varying(500),
    file_type character varying(50),
    remarks character varying(1000),
    released_by integer,
    released_at timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    appointment_id integer
);



--
-- Name: lab_results_lab_result_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.lab_results ALTER COLUMN lab_result_id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.lab_results_lab_result_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: laboratory_tests; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.laboratory_tests (
    test_id integer NOT NULL,
    test_name character varying(150) NOT NULL,
    category character varying(50) NOT NULL,
    price numeric(10,2) NOT NULL,
    preparation_instructions character varying(1000),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT laboratory_tests_category_check CHECK (((category)::text = ANY ((ARRAY['Clinical Diagnostics'::character varying, 'X-Ray'::character varying, 'Ultrasound'::character varying])::text[]))),
    CONSTRAINT laboratory_tests_price_check CHECK ((price >= (0)::numeric))
);



--
-- Name: laboratory_tests_test_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.laboratory_tests ALTER COLUMN test_id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.laboratory_tests_test_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notifications (
    notification_id integer NOT NULL,
    patient_id integer NOT NULL,
    notification_type character varying(50) NOT NULL,
    title character varying(150) NOT NULL,
    message character varying(500) NOT NULL,
    is_read boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);



--
-- Name: notifications_notification_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.notifications ALTER COLUMN notification_id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.notifications_notification_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: otp_codes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.otp_codes (
    otp_id integer NOT NULL,
    email character varying(255) NOT NULL,
    otp_code character varying(10) NOT NULL,
    type character varying(20) DEFAULT 'password'::character varying NOT NULL,
    expires_at timestamp without time zone NOT NULL,
    used boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);



--
-- Name: otp_codes_otp_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.otp_codes_otp_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;



--
-- Name: otp_codes_otp_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.otp_codes_otp_id_seq OWNED BY public.otp_codes.otp_id;


--
-- Name: patients; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.patients (
    patient_id integer NOT NULL,
    user_id integer,
    first_name character varying(100) NOT NULL,
    last_name character varying(100) NOT NULL,
    email character varying(255),
    phone character varying(30),
    address character varying(255),
    gender character varying(20),
    birth_date date,
    result_pin_hash character varying(255),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    mobile character varying(20),
    CONSTRAINT patients_gender_check CHECK (((gender)::text = ANY ((ARRAY['Male'::character varying, 'Female'::character varying, 'Other'::character varying])::text[])))
);



--
-- Name: patients_patient_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.patients ALTER COLUMN patient_id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.patients_patient_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: payments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payments (
    payment_id integer NOT NULL,
    bill_id integer NOT NULL,
    amount numeric(10,2) NOT NULL,
    payment_method character varying(30) NOT NULL,
    reference_number character varying(100),
    paid_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT payments_amount_check CHECK ((amount > (0)::numeric))
);



--
-- Name: payments_payment_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.payments ALTER COLUMN payment_id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.payments_payment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    user_id integer NOT NULL,
    email character varying(255) NOT NULL,
    password_hash character varying(255) NOT NULL,
    role character varying(30) NOT NULL,
    status character varying(20) DEFAULT 'Active'::character varying NOT NULL,
    last_active timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['Administrator'::character varying, 'Laboratory Staff'::character varying, 'Doctor'::character varying, 'Patient'::character varying])::text[]))),
    CONSTRAINT users_status_check CHECK (((status)::text = ANY ((ARRAY['Active'::character varying, 'Disabled'::character varying])::text[])))
);



--
-- Name: users_user_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.users ALTER COLUMN user_id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.users_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: announcements announcement_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.announcements ALTER COLUMN announcement_id SET DEFAULT nextval('public.announcements_announcement_id_seq'::regclass);


--
-- Name: audit_logs log_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN log_id SET DEFAULT nextval('public.audit_logs_log_id_seq'::regclass);


--
-- Name: otp_codes otp_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.otp_codes ALTER COLUMN otp_id SET DEFAULT nextval('public.otp_codes_otp_id_seq'::regclass);


--
-- Data for Name: announcements; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.announcements (announcement_id, title, description, image_url, is_active, created_at) FROM stdin;
1	Welcome to SLIMM Laboratory	We are now fully operational and ready to serve you. Book your appointments online.	\N	t	2026-09-26 12:49:30.730411
\.


--
-- Data for Name: appointment_details; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.appointment_details (appointment_detail_id, appointment_id, test_id, price_at_booking) FROM stdin;
2	2	2	300.00
3	3	2	500.00
4	4	3	800.00
\.


--
-- Data for Name: appointments; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.appointments (appointment_id, patient_id, doctor_id, appointment_date, appointment_time, status, notes, approved_by, approved_at, created_at, updated_at, expected_result_date) FROM stdin;
2	2	1	2026-10-15	09:00:00	Completed	\N	5	2026-10-15 18:52:35.992928	2026-10-15 18:51:53.007548	2026-10-15 18:53:24.397585	\N
3	3	1	2026-10-15	08:30:00	Pending	\N	\N	\N	2026-10-15 19:41:25.210949	2026-10-15 19:41:25.210949	\N
4	3	14	2026-10-19	10:30:00	Completed	\N	30	2026-10-15 19:49:20.681566	2026-10-15 19:48:55.401681	2026-10-15 19:50:04.380082	\N
\.


--
-- Data for Name: audit_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.audit_logs (log_id, user_id, action, details, ip_address, created_at) FROM stdin;
1	1	BOOK_APPOINTMENT	Booked appointment #1 for Blood Typing on 2026-10-17	\N	2026-10-15 16:26:34.558822
2	2	BOOK_APPOINTMENT	Booked appointment #2 for Blood Typing on 2026-10-15	\N	2026-10-15 18:51:53.007548
3	3	BOOK_APPOINTMENT	Booked appointment #3 for Blood Typing on 2026-10-15	\N	2026-10-15 19:41:25.210949
4	3	BOOK_APPOINTMENT	Booked appointment #4 for Chest X-Ray on 2026-10-19	\N	2026-10-15 19:48:55.401681
\.


--
-- Data for Name: bills; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bills (bill_id, appointment_id, total_amount, status, created_at) FROM stdin;
\.


--
-- Data for Name: doctor_schedule_blocks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.doctor_schedule_blocks (schedule_block_id, doctor_id, schedule_date, start_time, end_time, block_type, status, note, created_by, confirmed_at) FROM stdin;
2	1	2026-10-15	08:00:00	10:00:00	Whole Day	Confirmed		1	2026-10-15 16:14:04.174164
1	1	2026-10-16	08:00:00	17:00:00	Whole Day	Confirmed		1	2026-10-15 16:14:06.971482
3	1	2026-10-16	07:00:00	12:00:00	Morning	Confirmed		1	2026-10-15 16:23:43.648902
4	1	2026-10-17	08:00:00	12:00:00	Morning	Confirmed		1	2026-10-15 16:26:13.07427
5	1	2026-10-19	09:00:00	15:00:00	Whole Day	Awaiting doctor		1	\N
6	14	2026-10-19	08:00:00	12:00:00	Morning	Confirmed		1	2026-10-15 19:48:23.481625
\.


--
-- Data for Name: doctor_weekly_availability; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.doctor_weekly_availability (weekly_availability_id, doctor_id, day_of_week, start_time, end_time, is_active) FROM stdin;
4	1	1	08:00:00	17:00:00	t
5	1	2	13:00:00	17:00:00	t
6	1	3	08:00:00	17:00:00	t
24	14	0	08:00:00	17:00:00	t
25	14	1	08:00:00	17:00:00	t
\.


--
-- Data for Name: doctors; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.doctors (doctor_id, user_id, first_name, last_name, email, phone, specialization, profile_picture, created_at) FROM stdin;
1	5	John	Ruiz	johnruiz@gmail.com	09123456789	Pathology	\N	2026-09-28 02:53:30.07665
14	30	Marvy	Segara	marvy@gmail.com	09123456789	Radtech	\N	2026-10-15 19:45:16.391806
\.


--
-- Data for Name: lab_results; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.lab_results (lab_result_id, file_path, file_type, remarks, released_by, released_at, created_at, appointment_id) FROM stdin;
2	uploads/results/1792061604_dc3f1b80aecfff20f0c68be78a461119.jpg	image/jpeg		5	2026-10-15 18:53:24.386141	2026-10-15 18:53:24.386141	2
3	uploads/results/1792065004_dc3f1b80aecfff20f0c68be78a461119.jpg	image/jpeg		30	2026-10-15 19:50:04.364844	2026-10-15 19:50:04.364844	4
\.


--
-- Data for Name: laboratory_tests; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.laboratory_tests (test_id, test_name, category, price, preparation_instructions, is_active, created_at) FROM stdin;
1	Complete Blood Count (CBC)	Clinical Diagnostics	500.00	No special preparation required.	t	2026-09-23 04:11:34.213666
3	Chest X-Ray	X-Ray	800.00	Remove metal objects before the procedure.	t	2026-09-23 04:11:34.213666
4	Abdominal Ultrasound	Ultrasound	1200.00	Follow laboratory fasting instructions before the procedure.	t	2026-09-23 04:11:34.213666
2	Blood Typing	Clinical Diagnostics	500.00	No special preparation required.	t	2026-09-23 04:11:34.213666
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notifications (notification_id, patient_id, notification_type, title, message, is_read, created_at) FROM stdin;
5	2	appointment	Appointment Booked	Your Blood Typing appointment is scheduled on 2026-10-15 at 09:00:00. Status: Pending.	t	2026-10-15 18:51:53.007548
6	2	appointment	Appointment Confirmed	Your appointment has been confirmed by the doctor. See you soon!	t	2026-10-15 18:52:36.025598
8	2	result	Lab Result Ready	Your lab result is now available in the SLIMM Lab app.	t	2026-10-15 18:53:24.405194
7	2	appointment	Appointment Completed	Your appointment has been marked as Completed. Your lab results will be available shortly.	t	2026-10-15 18:52:49.717689
11	3	appointment	Appointment Confirmed	Your appointment has been confirmed by the doctor. See you soon!	t	2026-10-15 19:49:20.710248
10	3	appointment	Appointment Booked	Your Chest X-Ray appointment is scheduled on 2026-10-19 at 10:30:00. Status: Pending.	t	2026-10-15 19:48:55.401681
9	3	appointment	Appointment Booked	Your Blood Typing appointment is scheduled on 2026-10-15 at 08:30:00. Status: Pending.	t	2026-10-15 19:41:25.210949
12	3	appointment	Appointment Completed	Your appointment has been marked as Completed. Your lab results will be available shortly.	f	2026-10-15 19:49:44.41358
13	3	result	Lab Result Ready	Your lab result is now available in the SLIMM Lab app.	f	2026-10-15 19:50:04.389771
\.


--
-- Data for Name: otp_codes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.otp_codes (otp_id, email, otp_code, type, expires_at, used, created_at) FROM stdin;
\.


--
-- Data for Name: patients; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.patients (patient_id, user_id, first_name, last_name, email, phone, address, gender, birth_date, result_pin_hash, created_at, mobile) FROM stdin;
2	28	Oiz		\N	\N	cebu	Male	2026-10-15	$2y$10$dTHqHe1dcuW4SyJC696/vumSJEs3yUFDcKFPSMAKoO6VgA5Ifjaby	2026-10-15 18:50:57.359389	09123456789
3	29	Joenz	Ruiz	\N	\N	Cebu City	Male	2007-03-02	$2y$10$.k86Wtsr0WOajyzRN6regu3jtPc2hY3SXZBVuaFB5BhWsqWprxpcm	2026-10-15 19:33:32.334618	09123456789
\.


--
-- Data for Name: payments; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payments (payment_id, bill_id, amount, payment_method, reference_number, paid_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (user_id, email, password_hash, role, status, last_active, created_at) FROM stdin;
5	johnruiz@gmail.com	$2y$10$irYAdRauX1CcNOD1gBqDYuzJ/y8mxqM8pbTGiECYEwn2BoYTCtA1O	Doctor	Active	2026-10-15 18:46:27.093551	2026-09-28 04:58:13.545298
28	joenzsalibio@gmail.com	$2y$10$BxMG.M094ai.4pt2CpbHGO9BoIbOpQVZwHZriWGqeLaMAlTWUW/XS	Patient	Active	\N	2026-10-15 18:50:57.359389
29	joenzgulfan@gmail.com	$2y$10$j9bOkIm0UTdOVyEztxwOeu9JOFbCi1P3GYfmydb2HiPLhENRlGJWq	Patient	Active	\N	2026-10-15 19:33:32.334618
1	admin@slimmlaboratory.com	$2y$10$0lO05Idck4apttAu8D3/Zu.QQce3ZtU7zC2iNa3y9gQTONWWydy6e	Administrator	Active	2026-10-15 19:34:23.04745	2026-09-23 04:21:54.044363
30	marvy@gmail.com	$2y$10$rxVTuvfu2VlwHtBpIDmc5OvITsMWTwcoSkpsTJUV/pFG0JfWBY/BS	Doctor	Active	2026-10-15 19:48:08.441517	2026-10-15 19:46:43.969659
7	staff@slimmlaboratory.com	$2y$10$CkS/m3Zj.omBaakS30yEtuIZwoJuSQ98PAv/e.azVeEcnJKOx5dWu	Laboratory Staff	Active	2026-10-14 00:33:48.861073	2026-09-28 05:05:08.669803
\.


--
-- Name: announcements_announcement_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.announcements_announcement_id_seq', 1, true);


--
-- Name: appointment_details_appointment_detail_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.appointment_details_appointment_detail_id_seq', 4, true);


--
-- Name: appointments_appointment_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.appointments_appointment_id_seq', 4, true);


--
-- Name: audit_logs_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.audit_logs_log_id_seq', 4, true);


--
-- Name: bills_bill_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bills_bill_id_seq', 1, false);


--
-- Name: doctor_schedule_blocks_schedule_block_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.doctor_schedule_blocks_schedule_block_id_seq', 6, true);


--
-- Name: doctor_weekly_availability_weekly_availability_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.doctor_weekly_availability_weekly_availability_id_seq', 25, true);


--
-- Name: doctors_doctor_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.doctors_doctor_id_seq', 14, true);


--
-- Name: lab_results_lab_result_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.lab_results_lab_result_id_seq', 3, true);


--
-- Name: laboratory_tests_test_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.laboratory_tests_test_id_seq', 5, true);


--
-- Name: notifications_notification_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notifications_notification_id_seq', 13, true);


--
-- Name: otp_codes_otp_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.otp_codes_otp_id_seq', 1, false);


--
-- Name: patients_patient_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.patients_patient_id_seq', 3, true);


--
-- Name: payments_payment_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payments_payment_id_seq', 1, false);


--
-- Name: users_user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_user_id_seq', 30, true);


--
-- Name: announcements announcements_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_pkey PRIMARY KEY (announcement_id);


--
-- Name: appointment_details appointment_details_appointment_id_test_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.appointment_details
    ADD CONSTRAINT appointment_details_appointment_id_test_id_key UNIQUE (appointment_id, test_id);


--
-- Name: appointment_details appointment_details_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.appointment_details
    ADD CONSTRAINT appointment_details_pkey PRIMARY KEY (appointment_detail_id);


--
-- Name: appointments appointments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.appointments
    ADD CONSTRAINT appointments_pkey PRIMARY KEY (appointment_id);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (log_id);


--
-- Name: bills bills_appointment_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bills
    ADD CONSTRAINT bills_appointment_id_key UNIQUE (appointment_id);


--
-- Name: bills bills_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bills
    ADD CONSTRAINT bills_pkey PRIMARY KEY (bill_id);


--
-- Name: doctor_schedule_blocks doctor_schedule_blocks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.doctor_schedule_blocks
    ADD CONSTRAINT doctor_schedule_blocks_pkey PRIMARY KEY (schedule_block_id);


--
-- Name: doctor_weekly_availability doctor_weekly_availability_doctor_id_day_of_week_start_time_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.doctor_weekly_availability
    ADD CONSTRAINT doctor_weekly_availability_doctor_id_day_of_week_start_time_key UNIQUE (doctor_id, day_of_week, start_time, end_time);


--
-- Name: doctor_weekly_availability doctor_weekly_availability_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.doctor_weekly_availability
    ADD CONSTRAINT doctor_weekly_availability_pkey PRIMARY KEY (weekly_availability_id);


--
-- Name: doctors doctors_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.doctors
    ADD CONSTRAINT doctors_email_key UNIQUE (email);


--
-- Name: doctors doctors_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.doctors
    ADD CONSTRAINT doctors_pkey PRIMARY KEY (doctor_id);


--
-- Name: doctors doctors_user_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.doctors
    ADD CONSTRAINT doctors_user_id_key UNIQUE (user_id);


--
-- Name: lab_results lab_results_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lab_results
    ADD CONSTRAINT lab_results_pkey PRIMARY KEY (lab_result_id);


--
-- Name: laboratory_tests laboratory_tests_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.laboratory_tests
    ADD CONSTRAINT laboratory_tests_pkey PRIMARY KEY (test_id);


--
-- Name: laboratory_tests laboratory_tests_test_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.laboratory_tests
    ADD CONSTRAINT laboratory_tests_test_name_key UNIQUE (test_name);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (notification_id);


--
-- Name: otp_codes otp_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.otp_codes
    ADD CONSTRAINT otp_codes_pkey PRIMARY KEY (otp_id);


--
-- Name: patients patients_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.patients
    ADD CONSTRAINT patients_email_key UNIQUE (email);


--
-- Name: patients patients_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.patients
    ADD CONSTRAINT patients_pkey PRIMARY KEY (patient_id);


--
-- Name: patients patients_user_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.patients
    ADD CONSTRAINT patients_user_id_key UNIQUE (user_id);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (payment_id);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- Name: idx_appointment_details_appointment; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_appointment_details_appointment ON public.appointment_details USING btree (appointment_id);


--
-- Name: idx_appointments_doctor_date; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_appointments_doctor_date ON public.appointments USING btree (doctor_id, appointment_date);


--
-- Name: idx_appointments_patient; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_appointments_patient ON public.appointments USING btree (patient_id);


--
-- Name: idx_notifications_patient; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_notifications_patient ON public.notifications USING btree (patient_id, created_at DESC);


--
-- Name: idx_schedule_blocks_doctor_date; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_schedule_blocks_doctor_date ON public.doctor_schedule_blocks USING btree (doctor_id, schedule_date);


--
-- Name: appointment_details appointment_details_appointment_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.appointment_details
    ADD CONSTRAINT appointment_details_appointment_id_fkey FOREIGN KEY (appointment_id) REFERENCES public.appointments(appointment_id) ON DELETE CASCADE;


--
-- Name: appointment_details appointment_details_test_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.appointment_details
    ADD CONSTRAINT appointment_details_test_id_fkey FOREIGN KEY (test_id) REFERENCES public.laboratory_tests(test_id) ON DELETE RESTRICT;


--
-- Name: appointments appointments_approved_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.appointments
    ADD CONSTRAINT appointments_approved_by_fkey FOREIGN KEY (approved_by) REFERENCES public.users(user_id) ON DELETE SET NULL;


--
-- Name: appointments appointments_doctor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.appointments
    ADD CONSTRAINT appointments_doctor_id_fkey FOREIGN KEY (doctor_id) REFERENCES public.doctors(doctor_id) ON DELETE RESTRICT;


--
-- Name: appointments appointments_patient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.appointments
    ADD CONSTRAINT appointments_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patients(patient_id) ON DELETE RESTRICT;


--
-- Name: bills bills_appointment_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bills
    ADD CONSTRAINT bills_appointment_id_fkey FOREIGN KEY (appointment_id) REFERENCES public.appointments(appointment_id) ON DELETE RESTRICT;


--
-- Name: doctor_schedule_blocks doctor_schedule_blocks_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.doctor_schedule_blocks
    ADD CONSTRAINT doctor_schedule_blocks_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(user_id) ON DELETE SET NULL;


--
-- Name: doctor_schedule_blocks doctor_schedule_blocks_doctor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.doctor_schedule_blocks
    ADD CONSTRAINT doctor_schedule_blocks_doctor_id_fkey FOREIGN KEY (doctor_id) REFERENCES public.doctors(doctor_id) ON DELETE CASCADE;


--
-- Name: doctor_weekly_availability doctor_weekly_availability_doctor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.doctor_weekly_availability
    ADD CONSTRAINT doctor_weekly_availability_doctor_id_fkey FOREIGN KEY (doctor_id) REFERENCES public.doctors(doctor_id) ON DELETE CASCADE;


--
-- Name: doctors doctors_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.doctors
    ADD CONSTRAINT doctors_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(user_id) ON DELETE SET NULL;


--
-- Name: lab_results lab_results_released_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lab_results
    ADD CONSTRAINT lab_results_released_by_fkey FOREIGN KEY (released_by) REFERENCES public.users(user_id) ON DELETE SET NULL;


--
-- Name: notifications notifications_patient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patients(patient_id) ON DELETE CASCADE;


--
-- Name: patients patients_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.patients
    ADD CONSTRAINT patients_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(user_id) ON DELETE SET NULL;


--
-- Name: payments payments_bill_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_bill_id_fkey FOREIGN KEY (bill_id) REFERENCES public.bills(bill_id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict ryPb1XANIZlrkFoIWmUmQ62oZBbGIFhU2MOxLHLahOxKD2g7U4gtLKkFTPKzuyj

