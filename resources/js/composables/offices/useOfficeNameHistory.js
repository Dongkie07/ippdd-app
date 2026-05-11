const DEFAULT_OFFICE_NAME = 'Select an office'

export function resolveOfficeNameForYear(office, year) {
  if (!office) return DEFAULT_OFFICE_NAME

  const fiscalYear = Number(year)
  const histories = [...(office.histories ?? [])]
    .sort((left, right) => Number(right.effective_from_year ?? 0) - Number(left.effective_from_year ?? 0))

  const matchedHistory = histories.find((history) => {
    const fromYear = Number(history.effective_from_year ?? 0)
    const toYear = history.effective_to_year ? Number(history.effective_to_year) : null
    return fromYear <= fiscalYear && (!toYear || toYear >= fiscalYear)
  })

  return matchedHistory?.name ?? office.current_name ?? DEFAULT_OFFICE_NAME
}

export function officeLabel(office) {
  if (!office) return DEFAULT_OFFICE_NAME
  return office.acronym ? `${office.acronym} · ${office.current_name}` : office.current_name
}
