const state = {
  code: null,
  apiStatus: null
}

const mutations = {
  setCode (state, code) {
    state.code = code
  },
  setApiStatus (state, status) {
    state.apiStatus = status
  }
}

export default {
  namespaced: true,
  state,
  mutations
}