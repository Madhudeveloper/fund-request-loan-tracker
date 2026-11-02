import { useState, useEffect, useCallback } from 'react';
import api, { errMsg } from './api';
export function useDebounce(v, ms = 400) {
  const [d, setD] = useState(v);
  useEffect(() => { const t = setTimeout(() => setD(v), ms); return () => clearTimeout(t); }, [v, ms]);
  return d;
}
export function useFetch(url, params) {
  const [data, setData] = useState(null), [loading, setL] = useState(true), [error, setE] = useState('');
  const key = JSON.stringify(params);
  const load = useCallback(() => {
    setL(true);
    api.get(url, { params }).then(r => { setData(r.data); setE(''); }).catch(e => setE(errMsg(e))).finally(() => setL(false));
  }, [url, key]);
  useEffect(() => { load(); }, [load]);
  return { data, loading, error, reload: load };
}
