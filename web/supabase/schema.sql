-- =====================================================
-- SMART HEALTHCARE & DIAGNOSTIC MANAGEMENT SYSTEM
-- SUPABASE POSTGRESQL PRODUCTION SCHEMA + RLS POLICIES
-- =====================================================

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- =====================================================
-- 1. PROFILES TABLE (Linked 1:1 with Supabase auth.users)
-- =====================================================
CREATE TABLE IF NOT EXISTS public.profiles (
    id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    full_name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    role TEXT NOT NULL CHECK (role IN ('patient', 'doctor', 'admin')),
    phone TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'suspended')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- =====================================================
-- 2. PATIENTS TABLE
-- Profile data for role = 'patient'
-- =====================================================
CREATE TABLE IF NOT EXISTS public.patients (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE UNIQUE,
    age INT NOT NULL CHECK (age >= 1 AND age <= 120),
    weight NUMERIC(5,2) NOT NULL,
    cnic TEXT NOT NULL,
    insurance_number TEXT DEFAULT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- =====================================================
-- 3. DOCTORS TABLE
-- Profile data & working hours for role = 'doctor'
-- =====================================================
CREATE TABLE IF NOT EXISTS public.doctors (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE UNIQUE,
    specialization TEXT NOT NULL,
    clinic_address TEXT DEFAULT NULL,
    city TEXT DEFAULT NULL,
    available_from TIME NOT NULL DEFAULT '09:00:00',
    available_to TIME NOT NULL DEFAULT '17:00:00',
    consultation_fee NUMERIC(10,2) NOT NULL DEFAULT 500.00,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- =====================================================
-- 4. APPOINTMENTS TABLE
-- Booked appointments with priority severity and status
-- =====================================================
CREATE TABLE IF NOT EXISTS public.appointments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    patient_id UUID NOT NULL REFERENCES public.patients(id) ON DELETE CASCADE,
    doctor_id UUID NOT NULL REFERENCES public.doctors(id) ON DELETE CASCADE,
    severity_level TEXT NOT NULL CHECK (severity_level IN ('Emergency', 'Normal', 'Follow-up')),
    appointment_time TIMESTAMPTZ NOT NULL,
    status TEXT NOT NULL DEFAULT 'Pending' CHECK (status IN ('Pending', 'Confirmed', 'Completed', 'Cancelled')),
    symptoms_selected TEXT[] DEFAULT '{}',
    symptoms_text TEXT DEFAULT NULL,
    diagnosed_disease TEXT DEFAULT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- =====================================================
-- 5. DIAGNOSIS RULES TABLE
-- Scoring engine rules (mapped against symptoms)
-- =====================================================
CREATE TABLE IF NOT EXISTS public.diagnosis_rules (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    symptom_combination TEXT[] NOT NULL,
    possible_disease TEXT NOT NULL,
    advice TEXT NOT NULL,
    recommended_specialization TEXT NOT NULL DEFAULT 'General Physician',
    is_emergency BOOLEAN NOT NULL DEFAULT FALSE,
    first_aid_steps TEXT DEFAULT NULL
);

-- =====================================================
-- 6. BILLING TABLE
-- Auto-calculated billing records with 20% insurance discount
-- =====================================================
CREATE TABLE IF NOT EXISTS public.billing (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    appointment_id UUID NOT NULL REFERENCES public.appointments(id) ON DELETE CASCADE UNIQUE,
    consultation_fee NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    test_charges NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    insurance_discount_percent NUMERIC(5,2) NOT NULL DEFAULT 0.00,
    total_amount NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    payment_status TEXT NOT NULL DEFAULT 'Unpaid' CHECK (payment_status IN ('Unpaid', 'Paid')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- =====================================================
-- 7. ACTIVITY LOG TABLE
-- Audit trail of key administrative and clinical actions
-- =====================================================
CREATE TABLE IF NOT EXISTS public.activity_log (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    action TEXT NOT NULL,
    details TEXT DEFAULT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- =====================================================
-- 8. WARNINGS TABLE
-- Administrative warnings sent to users
-- =====================================================
CREATE TABLE IF NOT EXISTS public.warnings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    message TEXT NOT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- =====================================================
-- TRIGGER: Auto-create Profile on Supabase Auth Signup
-- =====================================================
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS trigger AS $$
BEGIN
  INSERT INTO public.profiles (id, full_name, email, role, phone, status)
  VALUES (
    new.id,
    COALESCE(new.raw_user_meta_data->>'full_name', 'New User'),
    new.email,
    COALESCE(new.raw_user_meta_data->>'role', 'patient'),
    COALESCE(new.raw_user_meta_data->>'phone', ''),
    'active'
  )
  ON CONFLICT (id) DO UPDATE SET
    full_name = EXCLUDED.full_name,
    role = EXCLUDED.role,
    phone = EXCLUDED.phone;
  RETURN new;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW EXECUTE PROCEDURE public.handle_new_user();

-- =====================================================
-- HELPER FUNCTIONS FOR ROW LEVEL SECURITY (RLS)
-- =====================================================
CREATE OR REPLACE FUNCTION public.is_admin()
RETURNS BOOLEAN AS $$
BEGIN
  RETURN EXISTS (
    SELECT 1 FROM public.profiles
    WHERE id = auth.uid() AND role = 'admin' AND status = 'active'
  );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER STABLE;

CREATE OR REPLACE FUNCTION public.get_user_role()
RETURNS TEXT AS $$
DECLARE
  u_role TEXT;
BEGIN
  SELECT role INTO u_role FROM public.profiles WHERE id = auth.uid();
  RETURN u_role;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER STABLE;

-- =====================================================
-- ROW LEVEL SECURITY (RLS) POLICIES
-- =====================================================

-- Enable RLS on all tables
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.patients ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.doctors ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.appointments ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.diagnosis_rules ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.billing ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.activity_log ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.warnings ENABLE ROW LEVEL SECURITY;

-- -----------------------------------------------------
-- PROFILES POLICIES
-- -----------------------------------------------------
-- Users can view their own profile, doctors can view patient profiles, admins can view all
CREATE POLICY "Users can view own profile or doctor/admin access"
  ON public.profiles FOR SELECT
  TO authenticated
  USING (
    auth.uid() = id OR public.is_admin() OR 
    (SELECT role FROM public.profiles WHERE id = auth.uid()) = 'doctor'
  );

CREATE POLICY "Users can update own profile"
  ON public.profiles FOR UPDATE
  TO authenticated
  USING (auth.uid() = id OR public.is_admin())
  WITH CHECK (auth.uid() = id OR public.is_admin());

-- -----------------------------------------------------
-- PATIENTS POLICIES
-- -----------------------------------------------------
CREATE POLICY "Patients can view/edit own patient record"
  ON public.patients FOR ALL
  TO authenticated
  USING (user_id = auth.uid() OR public.is_admin())
  WITH CHECK (user_id = auth.uid() OR public.is_admin());

CREATE POLICY "Doctors can view patient records"
  ON public.patients FOR SELECT
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM public.appointments a
      JOIN public.doctors d ON a.doctor_id = d.id
      WHERE a.patient_id = patients.id AND d.user_id = auth.uid()
    ) OR public.is_admin()
  );

-- -----------------------------------------------------
-- DOCTORS POLICIES
-- -----------------------------------------------------
-- Anyone can view doctors list to book appointments
CREATE POLICY "Anyone can view doctors"
  ON public.doctors FOR SELECT
  TO authenticated, anon
  USING (true);

CREATE POLICY "Doctors can update own profile"
  ON public.doctors FOR UPDATE
  TO authenticated
  USING (user_id = auth.uid() OR public.is_admin())
  WITH CHECK (user_id = auth.uid() OR public.is_admin());

CREATE POLICY "Doctors/Admins can insert doctor profile"
  ON public.doctors FOR INSERT
  TO authenticated
  WITH CHECK (user_id = auth.uid() OR public.is_admin());

-- -----------------------------------------------------
-- APPOINTMENTS POLICIES
-- -----------------------------------------------------
-- Patients view own, Doctors view assigned, Admins view all
CREATE POLICY "View appointments"
  ON public.appointments FOR SELECT
  TO authenticated
  USING (
    patient_id IN (SELECT id FROM public.patients WHERE user_id = auth.uid()) OR
    doctor_id IN (SELECT id FROM public.doctors WHERE user_id = auth.uid()) OR
    public.is_admin()
  );

CREATE POLICY "Patients can insert appointments"
  ON public.appointments FOR INSERT
  TO authenticated
  WITH CHECK (
    patient_id IN (SELECT id FROM public.patients WHERE user_id = auth.uid()) OR
    public.is_admin()
  );

CREATE POLICY "Update appointments"
  ON public.appointments FOR UPDATE
  TO authenticated
  USING (
    patient_id IN (SELECT id FROM public.patients WHERE user_id = auth.uid()) OR
    doctor_id IN (SELECT id FROM public.doctors WHERE user_id = auth.uid()) OR
    public.is_admin()
  );

-- -----------------------------------------------------
-- DIAGNOSIS RULES POLICIES
-- -----------------------------------------------------
-- Public read-only for assessment engine
CREATE POLICY "Public read diagnosis rules"
  ON public.diagnosis_rules FOR SELECT
  TO authenticated, anon
  USING (true);

CREATE POLICY "Admin manage diagnosis rules"
  ON public.diagnosis_rules FOR ALL
  TO authenticated
  USING (public.is_admin())
  WITH CHECK (public.is_admin());

-- -----------------------------------------------------
-- BILLING POLICIES
-- -----------------------------------------------------
CREATE POLICY "View billing"
  ON public.billing FOR SELECT
  TO authenticated
  USING (
    appointment_id IN (
      SELECT a.id FROM public.appointments a
      JOIN public.patients p ON a.patient_id = p.id
      WHERE p.user_id = auth.uid()
    ) OR
    appointment_id IN (
      SELECT a.id FROM public.appointments a
      JOIN public.doctors d ON a.doctor_id = d.id
      WHERE d.user_id = auth.uid()
    ) OR
    public.is_admin()
  );

CREATE POLICY "Doctors and Admins manage billing"
  ON public.billing FOR ALL
  TO authenticated
  USING (
    appointment_id IN (
      SELECT a.id FROM public.appointments a
      JOIN public.doctors d ON a.doctor_id = d.id
      WHERE d.user_id = auth.uid()
    ) OR
    public.is_admin()
  )
  WITH CHECK (
    appointment_id IN (
      SELECT a.id FROM public.appointments a
      JOIN public.doctors d ON a.doctor_id = d.id
      WHERE d.user_id = auth.uid()
    ) OR
    public.is_admin()
  );

-- -----------------------------------------------------
-- ACTIVITY LOG POLICIES
-- -----------------------------------------------------
CREATE POLICY "Admins can view activity logs"
  ON public.activity_log FOR SELECT
  TO authenticated
  USING (public.is_admin());

CREATE POLICY "Authenticated users can insert activity logs"
  ON public.activity_log FOR INSERT
  TO authenticated
  WITH CHECK (true);

-- -----------------------------------------------------
-- WARNINGS POLICIES
-- -----------------------------------------------------
CREATE POLICY "Users can view own warnings"
  ON public.warnings FOR SELECT
  TO authenticated
  USING (user_id = auth.uid() OR public.is_admin());

CREATE POLICY "Users can mark own warnings as read"
  ON public.warnings FOR UPDATE
  TO authenticated
  USING (user_id = auth.uid())
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "Admins can send warnings"
  ON public.warnings FOR INSERT
  TO authenticated
  WITH CHECK (public.is_admin());

-- =====================================================
-- SEED DATA: 7 Diagnostic Rules
-- =====================================================
INSERT INTO public.diagnosis_rules (symptom_combination, possible_disease, advice, recommended_specialization, is_emergency, first_aid_steps) VALUES
(ARRAY['fever','cough','body_ache','headache'],
 'Flu (Influenza)',
 'Rest well, drink plenty of fluids, and take over-the-counter fever reducers like paracetamol. See a doctor if symptoms persist beyond 3 days or if fever exceeds 103°F.',
 'General Physician', FALSE, NULL),

(ARRAY['fever','cough','shortness_of_breath'],
 'Respiratory Infection',
 'Seek medical attention promptly. Avoid smoking and dusty environments. A chest X-ray may be recommended by your doctor. Monitor breathing difficulty closely.',
 'Pulmonologist', FALSE, NULL),

(ARRAY['headache','nausea','sensitivity_to_light'],
 'Migraine',
 'Rest in a dark, quiet room. Stay hydrated and avoid screen time. Over-the-counter pain relievers may help. Consult a doctor if migraines occur frequently.',
 'Neurologist', FALSE, NULL),

(ARRAY['stomach_pain','vomiting','diarrhea','nausea'],
 'Food Poisoning',
 'Stay hydrated with ORS (oral rehydration salts). Avoid solid food until vomiting stops. Eat bland foods when recovering. Seek medical help if symptoms last more than 24 hours.',
 'Gastroenterologist', FALSE, NULL),

(ARRAY['chest_pain','shortness_of_breath','sweating'],
 'Cardiac Issue (Emergency)',
 'SEEK IMMEDIATE EMERGENCY MEDICAL ATTENTION. Do not delay — call an ambulance or go to the nearest emergency room immediately. Do not ignore chest pain combined with breathing difficulty.',
 'Cardiologist', TRUE,
 '1. Call emergency services (1122) or go to the nearest ER immediately.
2. Sit down or lie down in a comfortable position, avoid exertion.
3. Loosen any tight clothing.
4. Stay calm and avoid panic — try slow, steady breathing.
5. Do not drive yourself — have someone else drive or call an ambulance.
6. Do NOT take any medication unless already prescribed by your doctor for this exact condition.'),

(ARRAY['fever','body_ache','rash','joint_pain','headache'],
 'Dengue Fever',
 'Seek medical attention immediately. Stay hydrated with fluids. Platelet count monitoring is essential. Use mosquito nets.',
 'General Physician', TRUE,
 '1. Seek immediate medical evaluation at a hospital or clinic.
2. Rest in a comfortable place and stay hydrated with clean water or ORS.
3. Use mosquito nets to prevent further mosquito bites.
4. Monitor closely for warning signs such as severe abdominal pain or bleeding.
5. Do NOT take any unprescribed medications.'),

(ARRAY['sore_throat','fever','swollen_glands','headache'],
 'Throat Infection',
 'Gargle with warm salt water 3-4 times daily. Stay hydrated with warm fluids. Avoid cold drinks. Antibiotics may be needed — consult a doctor for proper prescription. Do not self-medicate.',
 'ENT Specialist', FALSE, NULL);
