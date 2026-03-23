import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import { PasswordResetProvider } from './context/PasswordResetContext';
import { Layout, ProtectedRoute, AdminRoute } from './components';
import {
  Login,
  Register,
  ForgotPassword,
  VerificationCode,
  ResetPassword,
  CompanyOnboarding,
  Landing,
  Privacy,
  Terms,
  Dashboard,
  AddProducts,
  Klientet,
  ClientSettings,
  Channels,
  ChannelDetail,
  Inbox,
  InboxThread,
  Contacts,
  ContactDetail,
  Automation,
  KeywordResponses,
  Settings,
  Profile,
  Business,
  Statistics,
  FeedbackOverview,
  OrdersDashboard,
} from './pages';

function PublicOnlyRoute({ children }: { children: React.ReactNode }) {
  const { token, loading } = useAuth();
  if (loading) return <div className="auth-loading">Duke ngarkuar…</div>;
  if (token) return <Navigate to="/app" replace />;
  return <>{children}</>;
}

function LandingOrRedirect() {
  const { token, loading } = useAuth();
  if (loading) return <div className="auth-loading">Duke ngarkuar…</div>;
  if (token) return <Navigate to="/app" replace />;
  return <Landing />;
}

function AppRoutes() {
  return (
    <Routes>
      <Route path="/" element={<LandingOrRedirect />} />
      <Route path="/privacy" element={<Privacy />} />
      <Route path="/terms" element={<Terms />} />
      <Route path="/login" element={<PublicOnlyRoute><Login /></PublicOnlyRoute>} />
      {/* Register qëndron i hapur edhe kur user-i sapo është autentikuar,
          që të mos pengohet ridrejtimi tek onboarding pas regjistrimit. */}
      <Route path="/register" element={<Register />} />
      <Route
        path="/forgot-password"
        element={
          <PublicOnlyRoute>
            <ForgotPassword />
          </PublicOnlyRoute>
        }
      />
      <Route
        path="/verify-code"
        element={
          <PublicOnlyRoute>
            <VerificationCode />
          </PublicOnlyRoute>
        }
      />
      <Route
        path="/reset-password"
        element={
          <PublicOnlyRoute>
            <ResetPassword />
          </PublicOnlyRoute>
        }
      />
      <Route
        path="/onboarding/company"
        element={
          <ProtectedRoute>
            <CompanyOnboarding />
          </ProtectedRoute>
        }
      />
      <Route
        path="/app"
        element={
          <ProtectedRoute>
            <Layout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Dashboard />} />
        <Route path="add-products" element={<AddProducts />} />
        <Route path="klientet" element={<AdminRoute><Klientet /></AdminRoute>} />
        <Route path="klientet/:userId/cilesime" element={<AdminRoute><ClientSettings /></AdminRoute>} />
        <Route path="profile" element={<Profile />} />
        <Route path="business" element={<Business />} />
        <Route path="statistics" element={<Statistics />} />
        <Route path="feedback" element={<FeedbackOverview />} />
        <Route path="channels" element={<Channels />} />
        <Route path="channels/:channelId" element={<ChannelDetail />} />
        <Route path="inbox" element={<Inbox />} />
        <Route path="inbox/:conversationId" element={<InboxThread />} />
        <Route path="orders" element={<OrdersDashboard />} />
        <Route path="contacts" element={<Contacts />} />
        <Route path="contacts/:contactId" element={<ContactDetail />} />
        <Route path="settings" element={<Settings />} />
        <Route path="automation" element={<Automation />} />
        <Route path="keyword-responses" element={<KeywordResponses />} />
        <Route path="chatbot" element={<Navigate to="/app/channels" replace />} />
        <Route path="manual-reply" element={<Navigate to="/app/inbox" replace />} />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <PasswordResetProvider>
          <AppRoutes />
        </PasswordResetProvider>
      </AuthProvider>
    </BrowserRouter>
  );
}
