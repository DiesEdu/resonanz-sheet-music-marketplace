const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

function getAuthHeaders() {
  const token = localStorage.getItem('auth_token')
  return token ? { Authorization: `Bearer ${token}` } : {}
}

export async function addSheetMusic(sheetData) {
  const response = await fetch(`${API_BASE_URL}/sheets`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...getAuthHeaders(),
    },
    body: JSON.stringify(sheetData),
  })

  const payload = await response.json().catch(() => null)

  if (!response.ok) {
    throw new Error(payload?.error || 'Failed to add sheet music')
  }

  return payload
}

export async function getUsers() {
  const response = await fetch(`${API_BASE_URL}/users`, {
    method: 'GET',
    headers: {
      ...getAuthHeaders(),
    },
  })

  const payload = await response.json().catch(() => null)

  if (!response.ok) {
    throw new Error(payload?.error || 'Failed to fetch users')
  }

  return payload
}

export async function getInstruments() {
  const response = await fetch(`${API_BASE_URL}/instruments`, {
    method: 'GET',
  })

  const payload = await response.json().catch(() => null)

  if (!response.ok) {
    throw new Error(payload?.error || 'Failed to fetch instruments')
  }

  return payload
}

export async function updateUserRole(userId, role) {
  const response = await fetch(`${API_BASE_URL}/users/${userId}/role`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      ...getAuthHeaders(),
    },
    body: JSON.stringify({ role }),
  })

  const payload = await response.json().catch(() => null)

  if (!response.ok) {
    throw new Error(payload?.error || 'Failed to update user role')
  }

  return payload
}
