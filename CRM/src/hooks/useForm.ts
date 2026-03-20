import type { ChangeEvent } from 'react';
import { useCallback, useMemo, useState } from 'react';

type Errors<T> = Partial<Record<keyof T, string>>;

export function useForm<T extends object>(initialValues: T) {
  const [values, setValues] = useState<T>(initialValues);
  const [errors, setErrors] = useState<Errors<T>>({});

  const setField = useCallback(<K extends keyof T>(key: K, value: T[K]) => {
    setValues((v) => ({ ...v, [key]: value }));
  }, []);

  const reset = useCallback(() => {
    setValues(initialValues);
    setErrors({});
  }, [initialValues]);

  const bind =
    useCallback(
      <K extends keyof T>(key: K) =>
        (e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
          const next = e.target.value as T[K];
          setField(key, next);
        },
      [setField],
    );

  const api = useMemo(
    () => ({
      values,
      errors,
      setErrors,
      setField,
      bind,
      reset,
    }),
    [values, errors, setErrors, setField, bind, reset]
  );

  return api;
}

