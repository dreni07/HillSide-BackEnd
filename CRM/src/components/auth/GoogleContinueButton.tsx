type GoogleContinueButtonProps = {
  disabled?: boolean;
  onClick: () => void;
  label?: string;
};

export function GoogleContinueButton({
  disabled,
  onClick,
  label = 'Continue with Google',
}: GoogleContinueButtonProps) {
  return (
    <button
      type="button"
      className="auth-google-btn"
      disabled={disabled}
      onClick={onClick}
      aria-label={label}
    >
      <span className="auth-google-icon" aria-hidden>
        G
      </span>
      {label}
    </button>
  );
}

