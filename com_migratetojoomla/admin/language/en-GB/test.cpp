  #include<bits/stdc++.h>
  using namespace std;
  long getNumberOfTriangles(int n, int m, vector<vector<int>> M) {
    int i, j;
    vector<vector<int>>dp_up(n, vector<int>(m, 0)); 
    vector<vector<int>>dp_down(n, vector<int>(m, 0)); 
    int ans = 0;
    for(i=n-1;i>=0;i--){
      for(j=m-1;j>=0;j--){

        if(!M[i][j])dp_up[i][j] = 1;

        if(i == n-1 ||  j == 0 || j == m-1)continue;

        else if(!M[i][j]){
          dp_up[i][j] = min(min(dp_up[i+1][j-1],dp_up[i+1][j]), dp_up[i+1][j+1]) + 1;
        }
      }
    }
    for(i=0;i<n;i++){
      for(j=0;j<m;j++){
        if(!M[i][j])dp_down[i][j] = 1;

        if(i == 0 || j == 0 || j == m-1)continue;

        else if(!M[i][j]){
          dp_down[i][j] = min(min(dp_down[i-1][j-1],dp_down[i-1][j]), dp_down[i-1][j+1]) + 1;
        }
      }
    }
    for(i=0;i<n;i++){
      for(j=0;j<m;j++){
        if(!M[i][j])ans += (dp_up[i][j] + dp_down[i][j] - 2);
      }
    }
    return ans;
  }


int main(int n , int m ,vector<string>M){

    vector<vector<int>>v(n , vector<int>(m , 0));

    for(int i=0;i<n;i++){
        for(int j=0;j<m;j++){
            if(M[i][j]=='1'){
                v[i][j]=1;
            }
        }
    }

    int ans  = getNumberOfTriangles( n , m ,v);
}

