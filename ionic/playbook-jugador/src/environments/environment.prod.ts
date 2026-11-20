declare const PLAYBOOK_API_URL: string | undefined;

export const environment = {
  production: true,
  apiUrl: typeof PLAYBOOK_API_URL === 'string' ? PLAYBOOK_API_URL : 'http://localhost:8000/api'
};
